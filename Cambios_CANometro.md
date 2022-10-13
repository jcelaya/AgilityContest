# Cambios realizados en el software de CANometro

## S.O.

- Cambiar la contraseña del usuario `pi`

## Red wifi

### Situación original
Por defecto, la red está configurada para que eth0 coja dirección por DHCP y wlan0 en modo hostap. `/etc/network/interfaces`:

    auto lo
    iface lo inet loopback
    iface eth0 inet dhcp
    allow-hotplug wlan0
    iface wlan0 inet static
    address 192.168.2.1
    netmask 255.255.255.0

El servicio hostapd está enabled. `/etc/hostapd/hostapd.conf`:

    interface=wlan0
    ssid=CANometroAP
    hw_mode=g
    channel=6
    macaddr_acl=0
    auth_algs=1
    ignore_broadcast_ssid=0
    wpa=2
    wpa_passphrase=CANometro
    wpa_key_mgmt=WPA-PSK
    wpa_pairwise=TKIP
    rsn_pairwise=CCMP

El servicio isc-dhcp-server está enabled. `/etc/default/isc-dhcp-server` contiene:

    INTERFACESv4="wlan0"
    INTERFACESv6="wlan0"

`/etc/dhcp/dhcpd.conf`:

    subnet 192.168.2.0 netmask 255.255.255.0 {
        range 192.168.2.2 192.168.2.30;
        option broadcast-address 192.168.2.255;
        option routers 192.168.2.1;
        default-lease-time 600;
        max-lease-time 7200;
        option domain-name "local";
        option domain-name-servers 8.8.8.8, 8.8.4.4;
    }

### Cambiar a conectar la wifi a una red concreta

He renombrado `/etc/network/interfaces` como `interfaces.hostap`. He añadido también el fichero `interfaces.dhcp`:

    auto lo
    iface lo inet loopback
    iface eth0 inet dhcp
    allow-hotplug wlan0
    iface wlan0 inet dhcp
    wpa-conf /etc/wpa_supplicant/laribera.conf

Y he hecho un enlace simbólico de `/etc/network/interfaces` a `/etc/network/interfaces.dhcp`. En `/etc/wpa_supplicant/laribera.conf`:

    ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
    update_config=1
    country=ES

    network={
            ssid="SSID_laribera"
            psk="pa$$w0rd"
    }

Finalmente, hay que deshabilitar los servicios `isc-dhcp-server` y `hostapd`:

    # systemctl disable isc-dhcp-server
    # systemctl disable hostapd

Para deshacer el cambio, simplemente cambiar el enlace simbólico y activar los servicios de nuevo.

## Crono

El script de inicio `/etc/rc.local` espera 5 segundos a que tengamos dirección por DHCP y entonces ejecuta el crono. El crono rastrea la subred buscando el bus de eventos de AgilityContest. Pero si no hemos recibido dirección IP antes de 5 segundos, no se puede hacer el rastreo.

Por lo tanto, hay que esperar a que:
- Si `wlan0` está configurado como DHCP
  - Que `wlan0` no pueda conectar con la red wifi, o
  - Que `wlan0` obtenga dirección IP
- Si `wlan0` está configurado como HostAP o no obtubo dirección IP
  - Que `eth0` no tenga carrier (cable no conectado), o
  - Que `eth0` tenga dirección IP válida

En cualquier caso, timeout a los 30 segundos.

    --- rc.local.orig       2022-10-01 22:23:51.629999915 +0100
    +++ rc.local            2022-10-01 22:30:23.511704731 +0100
    @@ -12,7 +12,12 @@
     # By default this script does nothing.

     # wait for dhclient to complete
    -sleep 5
	+for i in $(seq 1 30); do
    +    if grep -q "wlan0 inet dhcp" /etc/network/interfaces; then
    +       if ip addr show dev wlan0 | grep -q "inet "; then break; fi
    +    else
    +        if ip link show eth0 | grep -q NO-CARRIER; then break; fi
    +        if ip addr show dev eth0 | grep -q "inet "; then break; fi
    +    fi
    +    sleep 1;
    +done

     # Print the IP address
     _IP=$(hostname -I) || true

Configurar los valores por defecto en `/etc/crono.cfg`
- 7 minutos de reconocimiento

## Resto de la red

- El portátil con el programa debe estar en la ip `.2` para que el crono lo encuentre cuanto antes.
- Se puede usar el registro estático de DHCP

