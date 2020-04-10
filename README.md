This repository contains code samples for using the [Twikey api](https://twikey.com/api).

You can find code samples for eg.

* Creating a mandate to be signed by the customer (see specific language)
* Creating an invoice to be paid (see specific language)
* Verifying the signature of the webhook (see specific language)
* Verifying the signature of the exit url
* Calculating the OTP when using enhanced security

## Testing the API

### Via Postman

Postman is another excellent API Testing solution, the postman file can be found in the root of this repo or download an already customised one from your own environment in the API section.

### Via SoapUI

SoapUI is a free and open source cross-platform Functional Testing solution.

### Step-by-step Guide

* Download and install soapUI.
* Import the Twikey SoapUI project in SoapUI
* Open SoapUI
* Goto File – Import Project
* Choose the Twikey SoapUI project
* Select the 'Twikey API' project in SoapUI
* Choose 'Custom Properties' in the Properties section
* set 'host' to '[https://api.Twikey.com](https://api.twikey.com)'
* set 'salt', 'private_key', 'token' to match your ERP product and credentials
* Test Authentication
* Double click the 'Login' test case
* Run the test case
* Double click the 'login' test step
* The response is available in the right panel of the window, it should include a "AuthorizationToken"
* List All Mandates – JSON response
* Double click the 'List mandates – JSON' test case
* Double click the 'mandate JSON' test step
* Adapt the 'since' and 'chunkSize' request parameter in the left panel of the window
* Run the test case from the 'List mandates – JSON' test case window
* The response is available in the right panel of the 'mandate JSON' window
* List All Mandates – XML response
* Double click the 'List mandates – XML' test case
* Double click the 'mandate XML ' test step
* Adapt the 'since' and 'chunkSize' request parameter in the left panel of the window
* Run the test case from the 'List mandates – XML' test case window
* The response is available in the right panel of the 'mandate XML' window

## Verifying the signature of an exit url 

Given an exit url with the following value

    http:///website.com/{0}/{1}/{3}

this would be expanded to

    http:///website.com/mandatenumber/ok/C9FB0D93B4594F90069C3C23B4E0D25F3226EC2F6936DDA075643A660297E74B

given the following values:

    mandateNumber = "mandateNumber" //{0}
    status = "ok" //{1}
    signature =  hex encoded hmac256(privateKey,bytes) where

        websiteKey = "abcd" // can be downloaded from your settings / website
        bytes = (mandateNumber+"/"+status) decoded in utf8

        which results in C9FB0D93B4594F90069C3C23B4E0D25F3226EC2F6936DDA075643A660297E74B

### Decoding the account in the exit url 

If the account was in the exit url in order to avoid a backend call, you decrypt it using the following algorithm:

    account = "104CCC0FFEA2D76ED74CA02B57AE0EA045130C68C4FECEC57B784A0B8BE48F85" //

The iban/bic is also hex encoded and can be decrypted by using the md5 hash of the concatenated mandateNumber and websiteKey

    So http://website.com/
        ?mndt=TWIKEYCORE53
        &status=ok
        &acc=104CCC0FFEA2D76ED74CA02B57AE0EA045130C68C4FECEC57B784A0B8BE48F85
        &sig=98DA4F872B0A7B16B07DAF6B25A3247865AF91576FA870CBE6F6D2F695B4D7DD

    would be decrypted with cipher AES/CBC/PKCS5Padding and key md5(TWIKEYCORE53 + websiteKey) returning the account in a format iban/bic

## Calculation of the OTP

Twikey uses sha256 as hashing function using the [default step](https://tools.ietf.org/html/rfc6238#ref-UT) time of 30 seconds with the official unix time.
The return number is trimmed to 8 digits, More information is available in [RFC6238](https://tools.ietf.org/html/rfc6238)

Please check your language to have a code snippet calculating the otp.
