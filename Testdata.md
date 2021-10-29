# Introduction

When using our [test environment](https://www.beta.twikey.com) you should be able to test anything.

This can for example include:
- Email template configuration: body, subject, sender, (custom) attributes, ..
- (Failed) payment configurations and behaviour
- Webhooks
- API: feeds, invitations, updates, ..

This article also contains the data that can be used to emulate certain outcomes.

**Caution**: Emails can be sent out, be careful when testing.  
If necessary our support can enable an option to redirect all emails (including customer emails) to avoid any accidents.  
Contact your customer success agent to enable this.

# Table of contents
- [Transactions](#transactions)
- [B2B Mandate acceptance](#b2b-mandate-acceptance)
- [Sign methods and data](#sign-methods-and-data)
- [Letters](#letters)


## Transactions

Every 5 minutes, a job is run to mock feedback from the bank.  
Transactions sent to the bank then receive a paid or failure state with a specific error code depending on their amount.
You can test your failed payments configuration (dunning) using the specific failure codes.

    Insufficient funds (the CAN'T-BUCKET):
    * 1 to 5 euro - MS03
    * 6 to 10 euro - AM04

    Customer refuses (the WON'T-BUCKET):
    * 11 to 15 euro - MS02
    * 16 to 20 euro - MD06

    Failure at bank (the OTHERS-BUCKET):
    * 21 - AC01
    * 22 - AC04
    * 23 - AC06
    * 24 - AC13
    * 25 - AG01
    * 26 - FF01
    * 27 - MD01
    * 28 - MD02
    * 29 - MD07
    * 30 - PY01
    * 31 - RC01
    * 32 - SL01

    Paid:
    * 33 ≥ PAID


## B2B Mandate acceptance

Every 5 minutes, a job runs that accepts/rejects a mandate based on the last number of the iban.  
If the number is even, the B2B mandata will be accepted, but not for Belfius (BIC = GKCCBEBB) or BNP (BIC=GEBABEBB) account numbers.  
If the number is odd, the B2B mandate will be rejected.

Please find hereunder some examples for testing purposes:  
B2B Mandate accepted scenario --> BE16 6453 4897 1174  
B2B Mandate rejected scenario --> BE49 4732 1198 5171

## Sign methods and data

### iDeal, Emachtiging and iDin

Contact your customer support agent to link your template to a test bank.  
Provide a template ID and signing method.

### Other signing methods

- eID: with your own personal eID card
- Bank cards (Maestro): Any valid card number or 41111 or 4111111111111111
    - Expiry month = January: Hard failure
    - Expiry month **uneven**: Failure
    - Expiry month **even**: Success

- SMS: When inviting to sign via sms a mock email is sent to the email address configured under Settings -> Company information.  
  In the email there you can open a link. When opened you can:
    - Enter '**OK**': Success
    - Enter any other value: Failure

## Letters

When sending invites (documents, invoices, official WIK letter) note that no real letters are sent out.  
A copy of the letter is sent to you via email. This way you can verify the content before going live.  
