Doorstate
=========

Complete rewrite of [CCRX/doorstate](https://github.com/CCRRX/doorstate) in [Rust](https://www.rust-lang.org/).

Requirements
------------

### Client

- [Rust](https://www.rust-lang.org/)
- [Cargo](https://crates.io/)
- [WiringPi](http://wiringpi.com/)

#### Raspbian

	apt-get install build-essential libssl-dev wiringpi
	wget https://static.rust-lang.org/dist/rust-1.11.0-arm-unknown-linux-gnueabi.tar.gz
	tar xvf rust-1.11.0-arm-unknown-linux-gnueabihf.tar.gz
	cd rust-1.11.0-arm-unknown-linux-gnueabihf
	./install.sh

#### Mac OS X (Homebrew)

`brew install rust`

#### Gentoo

`emerge dev-lang/rust dev-util/cargo`

### Server

- [PHP](https://secure.php.net/) 5.2+

## Installation

### Client

	cd client
	cargo build --release --features pi
	cp target/release/doorstate .
