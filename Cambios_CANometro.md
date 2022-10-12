# Cambios realizados en el software de CANometro

## S.O.

- Cambiar la contraseña del usuario `pi`

## Crono

- Esperar a que `eth0` tenga una dirección IP para ejecutar el crono, en lugar de los 5 segundos fijos

    --- rc.local.orig       2022-10-01 22:23:51.629999915 +0100
    +++ rc.local            2022-10-01 22:30:23.511704731 +0100
    @@ -12,7 +12,12 @@
     # By default this script does nothing.

     # wait for dhclient to complete
    -sleep 5
    +for i in $(seq 1 30); do
    +    if ip link show eth0 | grep -q NO-CARRIER; then break; fi
    +    if ip addr show dev eth0 | grep -q "inet "; then break; fi
    +    sleep 1;
    +done

     # Print the IP address
     _IP=$(hostname -I) || true

- Configurar los valores por defecto en `/etc/crono.cfg`
  - 7 minutos de reconocimiento

## Red

- El portátil con el programa debe estar en la ip `.2` para que el crono lo encuentre cuanto antes.
- Se puede usar el registro estático de DHCP

