#!/bin/sh

g++ -O2 -Isha256/ sha256/sha256.cpp doorstate.cpp -o doorstate -lwiringPi -lcurl
