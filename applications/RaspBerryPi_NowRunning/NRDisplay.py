#!/usr/bin/env python3
# -*- coding: utf-8 -*-
#
# Copyright  2018-2021 by Juan Antonio Martinez ( juansgaviota at gmail dot com )
#
# This program is free software; you can redistribute it and/or modify it under the terms
# of the GNU General Public License as published by the Free Software Foundation;
# either version 2 of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
# without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
# See the GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along with this program;
# if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#

#system
import time
import math
from datetime import datetime

#image handler
from PIL import Image, ImageFont, ImageDraw

# devices
from luma.led_matrix.device import max7219
from luma.emulator.device import pygame
from lib import hub08 as hub08

from luma.core.interface.serial import spi, noop
from luma.core.render import canvas
from luma.core.legacy import text, show_message
from luma.core.virtual import viewport
from luma.core.legacy.font import proportional, CP437_FONT, TINY_FONT, SINCLAIR_FONT, LCD_FONT

class NRDisplay:

	DISPLAY = None # "max7219" or "pygame"
	loop = True
	# perro en pista
	nowRunning = 0
	# variables for message handling
	menuMessage = ""
	stdMessage = ""
	# values for setStdMessage
	categoria=''
	grado=''
	# variables for urgent message
	oobMessage = ""
	oobDuration = 1
	contrast=5
	glitch=0
	# modo reconocimiento de pista
	countDown=0
	# modo relok
	clockMode=False
	localTime = datetime.now()
	# modo cronometro
	lastChronoMode=0 # 0:off 1:stopped 2:running 3:countdown 4:error
	chronoMode=0 # 0:off 1:stopped 2:running 3:countdown 4:error
	startTime=0 # chrono provided start time
	stopTime=0 # chrono provided stop time

	# returns the elapsed milliseconds since the start of the program
	def millis(self):
		dt = datetime.now() - NRDisplay.localTime
		ms = (dt.days * 24 * 60 * 60 + dt.seconds) * 1000 + dt.microseconds / 1000.0
		return ms

	# 0: off 1:stopped 2:running 3:15 secs countdown
	def setChronoMode(self,value):
		# backup current mode. Used to handle sensor error/recovery
		NRDisplay.lastChronoMode = NRDisplay.chronoMode
		NRDisplay.chronoMode = value
		if value==0:
			NRDisplay.startTime=0
			NRDisplay.stopTime=0
			NRDisplay.localTime=datetime.now()
			
	def getChronoMode(self):
		return NRDisplay.chronoMode

	def chronoReady(self):
		self.setChronoMode(NRDisplay.lastChronoMode)

	def chronoError(self,value): # 1:error 0:ready
		if (value==1):
			self.setChronoMode(4)
		else:
			self.chronoReady()

	def chronoCountDown(self):
		NRDisplay.localTime=datetime.now()
		NRDisplay.startTime=self.millis()+15000
		self.setChronoMode(3)

	def chronoStart(self,val):
		NRDisplay.localTime=datetime.now()
		NRDisplay.startTime=val
		self.setChronoMode(2)

	def chronoStop(self,val):
		NRDisplay.stopTime=val
		self.setChronoMode(1)

	def chronoReset(self):
		NRDisplay.startTime=0
		NRDisplay.stopTime=0
		self.setChronoMode(1)

	# enable/disable clock mode (true,false)
	def setClockMode(self,value):
		NRDisplay.clockMode=value

	def getClockMode(self):
		return NRDisplay.clockMode

	# set countDowntime for course walk
	# value=seconds. 0 means stop
	def setCountDown(self,value):
		if value == 0:
			NRDisplay.countDown = 0
		else:
			NRDisplay.countDown=time.time() + float(value)

	def getCountDown(self):
		return NRDisplay.countDown

	# finalize display threads
	def stopDisplay(self):
		NRDisplay.loop=False

	# Operacion normal
	def setNowRunning(self,nr):
		if nr >= 0:
			NRDisplay.nowRunning = nr

	def getNowRunning(self):
		return NRDisplay.nowRunning

	def setNextRunning(self):
		NRDisplay.nowRunning = ( NRDisplay.nowRunning + 1 ) % 10000

	def setPrevRunning(self):
		nr=NRDisplay.nowRunning -1
		if nr < 0:
			nr=9999
		NRDisplay.nowRunning = nr

	def setRing(self,ring):
		self.ring = ring

	def getRing(self):
		return self.ring

	def setRoundInfo(self,manga,cat,grad):
		self.manga = cat
		self.categoria = cat
		self.grado = grad
		if grad == "": # en open no hay grados
			str= "%s %s" %(self.manga,self.categoria)
		elif cat == "": # evento llamada no incluye ni cat ni grad, solo nombre de la ronda
			str= "%s" %(self.manga)
		else: # datos provenientes del mando remoto o interfaz web
			str= "%s %s - %s" %(self.manga,self.categoria,self.grado)
		self.ronda = str

	def getCategoria(self):
		return self.categoria

	def getManga(self):
		return self.manga

	def getGrado(self):
		return self.grado

	def getRoundInfo(self):
		return self.ronda

	# ajuste del menu
	def setMenuMessage(self,str):
		NRDisplay.menuMessage=str

	def setOobMessage(self,msg,duration):
		#make sure that oob message is longer than 4 characters
		while len(msg) <= 4:
			msg = " " + msg
		NRDisplay.oobMessage = msg
		NRDisplay.oobDuration = duration

	def setBrightness(self,value):
		NRDisplay.contrast = value

	def getBrightness(self):
		return NRDisplay.contrast

	def setGlitch(self,value):
		NRDisplay.glitch = int(value)

	#
	# Inicializacion del display
	def initDisplay(self,cascaded,block_orientation,rotate):
		# create matrix device
		if NRDisplay.DISPLAY == "max7219":
			serial = spi(port=0, device=0, gpio=noop())
			# use default if not provided by method
			dev = max7219(serial, cascaded=cascaded or 4, block_orientation=block_orientation or -90, rotate=rotate or 2)
		elif NRDisplay.DISPLAY == "pygame":
			dev = pygame(width=32, height=8, rotate=0, mode="RGB", transform="scale2x", scale=2 )
		else: # hub08
			dev = hub08.hub08(width=64, height=16, rotate=0, mode="1")
		# set default bright level
		dev.contrast( int(5*255/9) )
		dev.show()
		print("Created device "+NRDisplay.DISPLAY)
		return dev

	#
	# Thread de generacion de los mensajes a presentar
	def setStdMessage(self):
		count = 0
		delay=15
		while NRDisplay.loop == True:
			msg = ""
			if NRDisplay.clockMode == True:
				msg = ""
			elif NRDisplay.countDown != 0:
				msg = "Running course walk"
				delay = 20
			elif (count%5) == 0:
				msg = "Ring %s %s" % ( self.ring , self.ronda)
			else:
				if NRDisplay.nowRunning == 0:
					msg = "Runing test dog"
					delay = 20
				else:
					msg = "Now running %03d" % ( NRDisplay.nowRunning )
			print("setStdMessage() "+msg)
			NRDisplay.stdMessage = msg
			time.sleep(delay)
			count = count + 1
		# while
		print("setStdMessageThread() exiting")
	# end def

	# same as luma.core.legacy.text() but duplicate font size
	def text2(self,draw, xy, txt, fill=None, font=None):
		"""
		Draw a legacy font starting at :py:attr:`x`, :py:attr:`y` using the
		prescribed fill and font.

		:param draw: A valid canvas to draw the text onto.
		:type draw: PIL.ImageDraw
		:param txt: The text string to display (must be ASCII only).
		:type txt: str
		:param xy: An ``(x, y)`` tuple denoting the top-left corner to draw the
			text.
		:type xy: tuple
		:param fill: The fill color to use (standard Pillow color name or RGB
			tuple).
		:param font: The font (from :py:mod:`luma.core.legacy.font`) to use.
		"""
		font = font or DEFAULT_FONT
		x, y = xy
		for ch in txt:
			for byte in font[ord(ch)]:
				for j in range(0,16,2):
					if byte & 0x01 > 0:
						draw.point((x, y + j), fill=fill)
						draw.point((x+1, y + j), fill=fill)
						draw.point((x, y + j + 1), fill=fill)
						draw.point((x+1, y + j + 1), fill=fill)
					byte >>= 1
				x += 2

	#
	# Bucle infinito de gestion de mensajes
	def displayLoop(self):
		oldmsg=""
		contrast=5
		sx=0
		while NRDisplay.loop == True:
			# si tenemos orden de glitch, jugamos con el contraste
			if NRDisplay.glitch != 0:
				NRDisplay.glitch = 0
				NRDisplay.device.contrast( 0 )
				time.sleep(0.5)
				NRDisplay.device.contrast( int(contrast*255/9) )
				continue
			# si cambia el contraste, reajustamos
			# lo tenemos que hacer desde este thread para evitar problemas de concurrencia
			# en el servidor Xcb
			if contrast != NRDisplay.contrast:
				contrast = NRDisplay.contrast
				NRDisplay.device.contrast( int(contrast*255/9) )
			# si menu activo pasa a visualizacion de menu
			if NRDisplay.menuMessage != "" :
				msg=NRDisplay.menuMessage
				sx=1
			# Los mensajes Out-Of-Band tienen precedencia absoluta
			elif NRDisplay.oobMessage != "":
				msg = NRDisplay.oobMessage
				NRDisplay.oobMessage = ""
				delay=NRDisplay.oobDuration * 0.02
				font=proportional(CP437_FONT)
			#
			# Modo cronometro
			# si el crono esta activado, mostramos crono
			elif NRDisplay.chronoMode == 4: # Sensor error
				msg="Error"
			elif NRDisplay.chronoMode == 3: # 15 sec countdown
				elapsed=NRDisplay.startTime-self.millis()
				if ellapsed<=0:
					self.chronoReset()
				secs= math.trunc(elapsed/1000)
				decs= math.trunc((elapsed%1000)/100)
				msg="%02d:%01d " %(secs,decs)
			elif NRDisplay.chronoMode == 2: # crono running
				elapsed=self.millis()
				secs= math.trunc(elapsed/1000)
				decs= math.trunc((elapsed%1000)/100)
				msg="%02d:%01d " %(secs,decs)
			elif NRDisplay.chronoMode == 1: # crono stopped
				tfinal = NRDisplay.stopTime - NRDisplay.startTime
				secs= math.trunc(tfinal/1000)
				cents= math.trunc((tfinal%1000)/10)
				msg="%02d:%02d" %(secs,cents)
			#
			# Modo reconocimiento de pista
			# si el temporizador está activo, mostramos tiempo restante
			elif NRDisplay.countDown != 0:
				remaining=NRDisplay.countDown - time.time()
				if remaining <= 0.0:
					txt="End of Course Walk"
					print(txt)
					self.setOobMessage(txt,2)
					NRDisplay.countDown=0 # will erase "Fin" msg at next iteration
				else:
					min = int(remaining/60)
					secs= int(remaining)%60
					msg="%d:%02d" %(min,secs)
					sx=1
			#
			# Modo reloj
			# si el reloj esta activo, presentamos la hora
			elif NRDisplay.clockMode == True:
				tm= time.localtime()
				if tm.tm_sec%2 == 0:
					msg = time.strftime("%H:%M",tm)
				else:
					msg = time.strftime("%H.%M",tm)
				sx=0
			#
			# si hay mensajes "normales" pendientes, muestralos
			elif NRDisplay.stdMessage != "":
				msg = NRDisplay.stdMessage
				NRDisplay.stdMessage = ""
				font=proportional(LCD_FONT)
				delay=0.03
			#
			# Modo "Perro en Pista"
			# arriving here means just print dog running
			else:
				sx=5
				msg="%03d" % (NRDisplay.nowRunning)

			#
			# una vez adivinado que hay que hacer llega la hora de pintar
			# time to display. check length for scroll or just show
			if oldmsg == msg:
				time.sleep( 0.5 if self.chronoMode==0 else 0.1 ) # crono refresh every 0.15 secs
				continue # do not repaint when not needed
			oldmsg = msg
			sy=0
			if NRDisplay.DISPLAY == 'hub08':
				sy=5 # vertical center message on screen
			nchars= len(msg)
			if nchars >5: # si mas de 5 caracteres imprimimos mensaje con scroll
				show_message( NRDisplay.device, msg, y_offset=sy,fill="white", font=font, scroll_delay=delay )
			else:
				with canvas(NRDisplay.device) as draw:
					if NRDisplay.DISPLAY == 'hub08':
						fnt = proportional(CP437_FONT) # required to avoid dup'd char overlap
						y=1
						if nchars == 5:
							x=0
						elif nchars == 4:
							x=8 # course walk: with proportional font ':' gets shifted
						else:
							x=10
						self.text2(draw, (x,y), msg, fill="white",font=fnt)
					else:
						fnt = CP437_FONT
						y=0
						if nchars == 5:
							fnt = proportional(CP437_FONT) # to fit 5 chars in 32x8 display
							x=0
						elif nchars == 4:
							x=2
						else:
							x=4
						text(draw, (x, y), msg, fill="white",font=fnt)
		# while loop=True
		NRDisplay.device.clear()
		NRDisplay.device.hide()
		print("displayLoopThread() exiting")
	# end thread loop


	#
	# Inicializacion de la clase
	def __init__(self,display,cascaded,block_orientation,rotate):
		# initialize vars
		NRDisplay.DISPLAY = display
		NRDisplay.loop = True
		NRDisplay.stdMessage = ""
		self.setBrightness(5)
		self.setMenuMessage("")
		self.setOobMessage( "Hello AgilityContest", 1)

		# informacion de ring y manga y perro en pista
		self.setRing(1)
		self.setRoundInfo("Agility","Large","Grado 1")
		self.setNowRunning(0)

		NRDisplay.device= self.initDisplay(cascaded,block_orientation,rotate)
