extern crate curl;
extern crate crypto_hash;

#[cfg(feature = "pi")]
extern crate wiringpi;

use std::path::Path;
use std::fs::File;
use std::error::Error;
use std::io::prelude::*;
use std::{thread, time};
use std::str;

use curl::easy::Easy;

use crypto_hash::{Algorithm, hex_digest};

#[cfg(feature = "pi")]
use wiringpi::pin::{WiringPi, InputPin};

static CONFIG_FILE_NAME: &'static str = "doorstate.config";

struct API {
    url: String,
    rate_ms: i64,
    pre_shared_key: String
}

struct GPIO {
    pin: i8
}

fn config() -> (API, GPIO)
{
    let path = Path::new(CONFIG_FILE_NAME);
    let path_display = path.display();

    let mut file = match File::open(&path) {
        Err(why) => panic!(
            "Couldn't open {}: {}",
            path_display,
            why.description()),
        Ok(file) => file,
    };

    let mut config = String::new();

    match file.read_to_string(&mut config) {
        Err(why) => panic!(
            "Couldn't read {}: {}",
            path_display,
            why.description()),
        //Ok(_) => print!("{} contains:\n{}", path_display, config),
        Ok(_) => {},
    };

    let mut api = API {
        url: String::new(),
        pre_shared_key: String::new(),
        rate_ms: -1
    };

    let mut gpio = GPIO {
        pin: -1
    };

    for line in config.lines() {
        // Ignore comments (lines starting with '#')
        if !line.trim().starts_with("#") {
            //println!("{}", line);
            let tokens: Vec<&str> = line.splitn(2, "=").collect();

            let key = tokens[0].trim();
            let value = tokens[1].trim();

            match key {
                "api.url" => api.url = value.to_string(),
                "api.rate-ms" => api.rate_ms = value.parse::<i64>().unwrap(),
                "api.pre-shared-key" => api.pre_shared_key = value.to_string(),
                "gpio.pin" => gpio.pin = value.parse::<i8>().unwrap(),
                _ => {},
            }
        }
    }

    (api, gpio)
}

#[cfg(feature = "pi")]
fn read_doorstate(pin: &InputPin<WiringPi>) -> String {
    let mut status: String  = "geschlossen".to_string();

    if pin.digital_read() == wiringpi::pin::Value::Low {
        status = "offen".to_string();
    }

    return status;
}

#[cfg(not(feature = "pi"))]
#[allow(unused_variables)]
fn read_doorstate(pin: &()) -> String {
    return "offen".to_string();
}

fn send_doorstate(baseurl: &String, pre_shared_key: &String, status: &String) -> Result<(), curl::Error> {
    let mut easy = Easy::new();
    let mut dst = Vec::new();

    easy.url(&baseurl)?;

    // This scope is required to end the borrow of dst
    {
        let mut transfer = easy.transfer();
        transfer.write_function(|data| {
            dst.extend_from_slice(data);
            Ok(data.len())
        }).unwrap();
        transfer.perform()?;
    }

    let challenge = str::from_utf8(&dst).unwrap();
    let mut response = dst.to_vec();

    response.extend_from_slice(pre_shared_key.as_bytes());

    let response_hashed = hex_digest(Algorithm::SHA256, response);
    let mut url: String = String::new();

    url.push_str(baseurl);
    url.push_str("?");
    url.push_str("challenge=");
    url.push_str(challenge);
    url.push_str("&");
    url.push_str("response=");
    url.push_str(&response_hashed);
    url.push_str("&");
    url.push_str("status=");
    url.push_str(status);

    easy.url(&url)?;

    let transfer = easy.transfer();

    transfer.perform()?;

    Ok(())
}

#[cfg(feature = "pi")]
fn setup_gpio(pin_no: i8) -> InputPin<WiringPi> {
    let pi = wiringpi::setup();
    let pin = pi.input_pin(pin_no as u16);

    pin
}

#[cfg(not(feature = "pi"))]
#[allow(unused_variables)]
fn setup_gpio(pin: i8) {
}

fn main() {
    let (api, gpio) = config();
    let duration;
    let mut status_last: String = "".to_string();

    let pin = setup_gpio(gpio.pin);

    if api.rate_ms > -1 {
        duration = time::Duration::from_millis(api.rate_ms as u64);
    } else {
        duration = time::Duration::from_millis(0);
    }

    loop {
        let status_new = read_doorstate(&pin);

        if status_new != status_last {
            match send_doorstate(&api.url, &api.pre_shared_key, &status_new) {
                Ok(_) => {
                    println!("OK");
                    status_last = status_new;
                },
                Err(e) => println!("Error: {}", e)
            }

        }

        if duration > time::Duration::from_millis(0) {
            thread::sleep(duration);
        }
    }
}
