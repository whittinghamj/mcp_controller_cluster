#!/usr/bin/env python

import random
import time
import sys
import blinkt

color_one=sys.argv[1]
color_two=sys.argv[2]
color_three=sys.argv[3]

blinkt.set_clear_on_exit()

while True:
    pixels = random.sample(range(blinkt.NUM_PIXELS), random.randint(1, 5))
    for i in range(blinkt.NUM_PIXELS):
        if i in pixels:
            blinkt.set_pixel(i, color_one, color_two, color_three)
        else:
            blinkt.set_pixel(i, 0, 0, 0)
    blinkt.show()
    time.sleep(0.05)
