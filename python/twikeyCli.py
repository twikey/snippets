#!/usr/bin/env python

import sys
import os
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
import datetime
import cmd
import ConfigParser


class Twikey(cmd.Cmd):

    OKGREEN = '\033[92m'
    ENDC = '\033[0m'

    authToken = None
    mimeType = "application/json"

    def __init__(self,name,apiToken,privateKey,salt='own',url='https://api.beta.twikey.com/creditor'):
        cmd.Cmd.__init__(self)
        self.name = name
        self.prompt = name + ' => '
        self.baseUrl = url
        self.salt = salt
        self.apiToken = apiToken
        self.privateKey = privateKey

    def get_otp(self):
        """Return the Time-Based One-Time Password for the current time, and the provided secret (base32 encoded)
        """
        vendorPrefix = self.salt
        secret = self.privateKey
        counter = int(time.time()) // 30
        secret  = bytearray(vendorPrefix)+binascii.unhexlify(secret)
        counter = struct.pack('>Q', counter)
        hash   = hmac.new(secret, counter, hashlib.sha256).digest()
        offset = ord(hash[19]) & 0xF
        return (struct.unpack(">I", hash[offset:offset + 4])[0] & 0x7FFFFFFF) % 100000000

    def do_login(self,args):
        """ Login to Twikey
        """
        otp = self.get_otp()
        params = urllib.urlencode({'apiToken': self.apiToken, 'otp':otp})
        headers = {"Content-type": "application/x-www-form-urlencoded","Accept": "text/xml"}
        req = urllib2.Request(self.baseUrl , params)
        resp = urllib2.urlopen(req).read()
        auth = json.loads(resp)
        if 'Authorization' in auth:
            self.authToken = auth['Authorization']
            print "Login in %s" % self.baseUrl
            self.prompt = self.OKGREEN + self.name+" => "+self.ENDC

    def preloop(self):
        self.do_login(self)

    def do_mandate(self,_type):
        """Return the mandates
        """
        if self.authToken:
            req = urllib2.Request(self.baseUrl+"/mandate?chunkSize=40")
            req.add_header("Authorization",self.authToken)
            req.add_header("Accept",self.mimeType)
            response = urllib2.urlopen(req)
            resp = response.read()
            mandates = json.loads(resp)

            print json.dumps(mandates, sort_keys=True,indent=4, separators=(',', ': '))

    def do_transactions(self,_type):
        """Handle the transaction updates
        """
        if self.authToken:
            req = urllib2.Request(self.baseUrl+"/transaction?chunkSize=40")
            req.add_header("Authorization",self.authToken)
            req.add_header("Accept",self.mimeType)
            response = urllib2.urlopen(req)
            resp = response.read()
            mandates = json.loads(resp)

            print json.dumps(mandates, sort_keys=True,indent=4, separators=(',', ': '))

    def do_newtransaction(self,args):
        """Add a Transaction : <mndtId> <amount> <message>
        """
        items = args.split()
        if self.authToken:
            params = urllib.urlencode({'mndtId':items[0],'amount':items[1],'message':' '.join(items[2:])})
            req = urllib2.Request(self.baseUrl+"/billing",params)
            req.add_header("Authorization",self.authToken)
            req.add_header("Accept",self.mimeType)
            response = urllib2.urlopen(req)
            print response.read()

    def do_collect(self,args):
        """Send collection : <ct> <iban> <bic>
        """
        items = args.split()
        if self.authToken:
            params = urllib.urlencode({'ct':items[0],'iban':items[1],'bic':items[2]})
            print params
            req = urllib2.Request(self.baseUrl+"/collect",params)
            req.add_header("Authorization",self.authToken)
            req.add_header("Accept",self.mimeType)
            response = urllib2.urlopen(req)
            print response.read()

    def do_action(self,args):
        """Action mandate: <action> <creditorId> <mndt> <rsn>
        """
        items = args.split()
        params = urllib.urlencode({'creditorId':items[1],'mndtId':items[2],'rsn':' '.join(items[3:])})
        if self.authToken:
            req = urllib2.Request(self.baseUrl+"/mandate/"+str(items[0]),params)
            req.add_header("Authorization",self.authToken)
            req.add_header("Accept",self.mimeType)
            response = urllib2.urlopen(req)
            resp = response.read()
            print resp

    def do_quit(self,args):
        return "stop"
    def do_exit(self,args):
        return "stop"

    def postloop(self):
        if self.authToken:
            req = urllib2.Request(self.baseUrl,None)
            req.add_header("Authorization",self.authToken)
            req.add_header("Accept",self.mimeType)
            response = urllib2.urlopen(req)
            resp = response.read()
            print "Logged out"
            self.prompt = "=> "

    def default(self, line):
        print "Unknown command : %s" % line

if __name__ == '__main__':
    config = ConfigParser.SafeConfigParser({'salt':'own','url':'https://api.twikey.com/creditor'})

    config.read(['twikey.cfg', os.path.expanduser('~/.twikey.cfg')])

    if len(sys.argv) > 1:
        env = sys.argv[1]

        apiToken = config.get(env,'apiToken')
        privateKey = config.get(env,'privateKey')
        salt = config.get(env,'salt')
        url = config.get(env,'url')

        Twikey(env,apiToken,privateKey,salt,url).cmdloop()
    else:
        print "Please provide environment"
        print config.sections()
