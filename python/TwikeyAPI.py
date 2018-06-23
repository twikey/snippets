#!/usr/bin/env python

import hmac
import time
import base64
import struct
import hashlib
import binascii
import httplib
import urllib
import urllib2
import json
import sys
 
def get_totp(vendorPrefix,secret):
    """Return the Time-Based One-Time Password for the current time, and the provided secret (base32 encoded)
    """

    secret  = bytearray(vendorPrefix)+binascii.unhexlify(secret)
    counter = struct.pack('>Q', int(time.time()) // 30)
 
    hash   = hmac.new(secret, counter, hashlib.sha256).digest()
    offset = ord(hash[19]) & 0xF
 
    return (struct.unpack(">I", hash[offset:offset + 4])[0] & 0x7FFFFFFF) % 100000000
 
if __name__ == '__main__':
    otp = get_totp('salt','FC7BDEDA40BA9C5B292EE7A8331A55EDE20A61F9')
    
    ct = 1
    url = "https://api.twikey.com"
    params = urllib.urlencode({'apiToken': 'API_TOKEN', 'otp':otp})
    req = urllib2.Request(url+"/creditor", params)
    req.add_header("Accept","application/json")
    req.add_header("Content-type", "application/x-www-form-urlencoded")
    response = urllib2.urlopen(req)
    if response.headers["ApiErrorCode"]:
        #print response.headers
        print "Error authenticating : %s - %s" % (response.headers["ApiErrorCode"],response.headers["ApiError"])
        sys.exit(1)
        
    authorization = response.headers["Authorization"]    
    params = urllib.urlencode({
        "ct": ct,
        "email": "info@twikey.com",
        "firstname": "Info",
        "lastname": "Twikey",
        "l": "en",
        "address": "Abby road",
        "city": "Liverpool",
        "zip": "1526",
        "country": "BE",
        "mobile": "",
        "iban": "",
        "bic": "",
        "mandateNumber": "",
        "contractNumber": ""
    })
    
    req = urllib2.Request(url+"/creditor/prepare",params)
    req.add_header("Content-type", "application/x-www-form-urlencoded")
    req.add_header("Authorization",authorization)
    req.add_header("Accept","application/json")
    response = urllib2.urlopen(req)
    
    invite = json.loads(response.read())
    print "Redirecting to "+invite["url"]
