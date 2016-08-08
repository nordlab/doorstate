Doorstate
=========

Dependencies
------------

- [WiringPi](http://wiringpi.com/)
- cURL

This distribution includes a C++ SHA256 function by
http://www.zedwood.com/article/cpp-sha256-function
See `sha256/LICENSE.txt` for license.

Compilation
-----------

Compile by running

    g++ -O2 -Isha256/ sha256/sha256.cpp doorstate.cpp -o doorstate -lwiringPi -lcurl

or by running `make.sh`.

Installation
------------

1. Simply copy doorstate to an arbitrary location.
2. Copy `doorstate.init` to `/etc/init.d/doorstate`
3. Add init script to runlevel
4. Start daemon
