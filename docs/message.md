# Mandate feed structure

## High level structure

When a mandate is signed, the mandate's meta data is put available via the [mandate feed](https://www.twikey.com/api/#update-feed).
The high level structure can actually be read efficiently by using the logic _if no AmdmntRsn nor CxlRsn are available it is a new mandate._

* [GrpHdr](#grphdr--groupheader)
    * CreDtTm: date when the response was generated
* Messages
    * List
        * [MndtInitn](#mndtinitn--mandateinitation)
            * [Mndt](#Mndt) : Mandate details
            * [EvtTime](#credttm--creationdatetime) : Time of signing
        * [MndtAmdmnt](#mndtamdmnt--mandateamendment)
            * AmdmntRsn : Reason and originator of the change
            * [Mndt](#Mndt) : Mandate
            * [EvtTime](#credttm--creationdatetime) : Time of change
            * OrgnlMndtId : Reference to the signed mandate number
        * [MndtCxl](#mndtcxl--mandatecancellation)
            * CxlRsn : Reason and originator of the cancel
            * [EvtTime](#credttm--creationdatetime) : Time of cancel
            * OrgnlMndtId : Reference to the signed mandate number

## Mndt

Mndt contains the following fields

* MndtId: MandateIdentification,
* [LclInstrm](#lclinstrm--local-instrument): Scheme used - Core or B2B ,
* Ocrncs: Occurrences
* MaxAmt: Currency,
* CdtrSchmeId: string
* Cdtr: Merchant (Party)
* UltmtCdtr: Party,
* [Dbtr](#dbtr---debtor): Customer (Party),
* [DbtrAcct](#dbtracct--debtoraccount): Customer Account (string),
* [DbtrAgt](#dbtragt--debtoragent): Customer Bank (Agent),
* UltmtDbtr: Party,
* PrefExMmnt: PreferredExecutionMoment,
* RfrdDoc: string,
* [SplmtryData](#splmtrydata--supplementarydata): AdditionalData

Since the number of items in SplmtryData can be substantial an optional header can be passed to limit the data being returned in
this tag. By adding the "SplmtrydataMask" header with one or more of the values below, the data can be controlled.

* Mandate : All mandate related information (id, name, attribute values)
* Person : First and last name of the person
* Tracker : If a tracker was used for obtaining the contract
* Signature : All signatures ("SignerMethod\#","Signer\#","SignerPlace\#","SignerDate\#")
* SignaturePayload : All signatures' actual signature ("SignerPayload\#")
* SignatureAndPayload : All signatures including actual signature ("SignerMethod\#","Signer\#","SignerPlace\#","SignerDate\#","SignerPayload\#")
* Plan : Name of the billing plan linked to this contract


Listing and importing mandates needs some additional knowledge of the fields. 
To reduce implementation time for partners and banks, Twikey’s API is based on the fields used in the ISO20022 XML standard. 
Twikey added only a small number of extra fields in order to be usable by other stakeholders.


### GrpHdr – GroupHeader

**Definition**: Set of elements used to provide the meta data about the exchanged message.

**Tag**: GrpHdr

**Occurrences**: [1..1]

**Level**: 1

**Format**: See section ‘GroupHeader’

### MndtInitn – MandateInitation

**Definition**: Set of elements used to provide the initial data of a mandate.

**Tag**: MndtInitn

**Occurrences**: [0..*]

**Level**: 1

**Format**: See section ‘Mandate

### MndtAmdmnt – MandateAmendment

**Definition**: Set of elements used to provide the amendment reason and the updated data of a mandate.

**Tag**: MndtAmdmnt

**Occurrences**: [0..*]

**Level**: 1

**Format**: See sections ‘Reason and Mandate

### MndtCxl – MandateCancellation

**Definition**: Set of elements used to provide the cancellation reason and the reference to a mandate.

**Tag**: MndtCxl

**Occurrences**: [0..*]

**Level**: 1

**Format**: See sections ‘Reason and Mandate

## 2.  GroupHeader

### CreDtTm – CreationDateTime

**Definition**: Date and time at which the message was created.

**Tag**: CreDtTm

**Occurrences**: [1..1]

**Level**: 2

**Format**: YYYY-MM-DDThh:mm:ssZ (the ISODateTime format)

**Usage**: We use the creation date/time of the mandate creation with UTC as time zone (hence the ‘Z’ as postfix).

**XML Example**:

```xml 
<CreDtTm>2009-12-02T08:35:30Z</CreDtTm>
```

**JSON Example**:

```json
"CreDtTm": "2009-12-02T08:35:30Z"
```

## 3. AmdmntRsn / CxlRsn - Reason

### Orgtr – Originator

**Definition**: The party that made the change to the mandate.

Like all parties (Orgtr/Cdtr and Dbtr) information has 1 key for persons (email) and 2 for companies (ID and email).

**Tag**: Orgtr

**Occurrences**: [1..1]

**Level**: 2

**Format**: See section 'Party'

### Rsn – Reason

**Definition**: A description with the reason for the change

**Tag**: Rsn

**Occurrences**: [1..1]

**Level**: 2

**XML Example**:

```xml
<Rsn>Contract terminated</Rsn>
```

**JSON Example**:

```json
"Rsn": "Contract terminated"
```

## 4. Mandate

### MndtId/OrgnlMndtId \- MandateIdentification

**Definition**: Unique mandate number, as assigned by Twikey based on the prefix provided by the creditor, to unambiguously identify the mandate. This mandate number has to be communicated to the banks.

**Tag**: MndtId / OrgnlMndtId

**Occurrences**: [1..1]

**Level**: 1

**Format**: Max35Text (use UPPERCASE)

**Usage**: we provide the mandate number in this field

**XML Example**:

```xml
<MndtId>TWIKEY000021928</MndtId>
```

**JSON Example**:

```json
"MndtId": "TWIKEY000021928"
```

### LclInstrm – Local Instrument

**Definition**: Identifies whether the mandate is a B2B or Core mandate

**Tag**: LclInstrm

**Occurrences**: [0..1]

**Level**: 1

**Format**: Code

**XML Example**:

```xml
<LclInstrm>B2B</LclInstrm>
```

**JSON Example**:

```json
"LclInstrm": "B2B"
```

### Ocrncs – Occurrences

**Definition**: Provides details of the duration of the mandate and occurrence of the underlying transactions.

**Tag**: Ocrncs

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section 'Occurences'

### MaxAmt – MaximumAmount

**Definition**: Maximum amount that may be collected from the debtor's account, per instruction.

**Tag**: MaxAmt

**Occurrences**: [0..1]

**Level**: 1

**Format**:

* ActiveCurrencyAndAmount
    * fractionDigits: 5
    * minInclusive: 0
    * totalDigits: 18

* ActiveCurrencyCode
    * [A-Z]{3,3}

**Rules**: ActiveCurrencyAndAmount

* CurrencyAmount : The number of fractional digits (or minor unit of currency) must comply with ISO 4217. (Note: The decimal separator is a dot.)
* ActiveCurrencyCode:
    * ActiveCurrency: The currency code must be a valid active currency code, not yet withdrawn on the day the message containing the currency is exchanged. Valid active currency codes are registered with the ISO 4217 Maintenance Agency, consist of three (3) contiguous letters, and are not yet withdrawn on the day the message containing the Currency is exchanged.

**Usage**: the currency code is always EUR; For XML we provide EUR as a Ccy attribute, in JSON it is omitted.

**XML Example**:

```xml
<MaxAmt Ccy="EUR">6545.56</MaxAmt>
```

**JSON Example**:

```json
"MaxAmt": "6545.56"
```

### CdtrSchmeId – CreditorSchemeIdentification

**Definition**: Provides the creditor scheme identification. This is the identification assigned by government/bank to uniquely identify the creditor in combination with optional info filled by the creditor.

**Tag**: CdtrSchmeId

**Occurrences**: [0..1]

**Level**: 1

**Format**: String

**Usage**: used to provide the Creditor Identifier

### Cdtr – Creditor

**Definition**: Party that signs the mandate and to whom an amount of money is due.

Like all parties (Orgtr/Cdtr and Dbtr) information has 1 key for persons (email) and 2 for companies (ID and email).

**Tag**: Cdtr

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section ‘Party’

### CdtrAgt – CreditorAgent

**Definition**: Financial institution servicing an account for the creditor.

**Tag**: CdtrAgt

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section ‘Agent’

**Usage**: currently empty, reserved for future use.

### UltmtCdtr – UltimateCreditor

**Definition**: Ultimate party to which an amount of money is due Ultimate Creditor is only to be used if different from Creditor.

**Tag**: UltmtCdtr

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section ‘Party’

### Dbtr - Debtor

**Definition**: Party that owes an amount of money to the (ultimate) creditor.

Like all parties (Orgtr/Cdtr and Dbtr) information has 1 key for persons (email) and 2 for companies (ID and email).

**Tag**: Dbtr

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section ‘Party’

### DbtrAcct – DebtorAccount

**Definition**: Unambiguous identification of the account of the debtor, to which a debit entry will be made as a  result of the transaction.

**Tag**: DbtrAcct

**Occurrences**: [0..1]

**Level**: 1

**Format**: [A-Z]{2,2}[0-9]{2,2}[a-zA-Z0-9]{1,30}

**Rule(s)**: A valid IBAN consists of all three of the following components: Country Code, check digits and BBAN.

**Usage**: we fill in the valid IBAN account number of the debtor’s account. This field is absent if reception of this field has been acknowledged by the API user.

**XML Example**:

```xml
<DbtrAcct>BE15380051920030</DbtrAcct>
```

**JSON Example**:

```json
"DbtrAcct": "BE15380051920030"
```

### DbtrAgt – DebtorAgent

**Definition**: Financial institution servicing an account for the debtor.

**Tag**: DbtrAgt

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section ‘Agent’

### UltmtDbtr - UltimateDebtor


**Definition**: Ultimate party that owes an amount of money to the (ultimate) creditor. Ultimate Debtor is only to be used if different from Debtor.

**Tag**: UltmtDbtr

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section ‘Party’

### PrefExMnt – PreferredExecutionMoment

**Definition**: the debtor’s preferred moment for mandate related payments to happen.

**Tag**: PrefExMmnt

**Occurrences**: [0..1]

**Level**: 1

**Type**: See section 'Preferred Execution Moment'

### RfrdDoc – ReferredDocument

**Definition**: Provides information to identify the underlying documents associated with the mandate.

**Tag**: RfrdDoc

**Occurrences**: [0..1]

**Level**: 1

**Type**: string

**Usage**: Identification fo the underlying contract. Unique value per mandate template.

**XML Example**:

```xml
<RfrdDoc>2010112122622365513</RfrdDoc>
```

**JSON Example**:

```json
"RfrdDoc": "2010112122622365513"
```

### SplmtryData – SupplementaryData


**Definition**: Contains all available additional fields for the mandate. These fields are defined in the Creditor GUI of Twikey per "contract template". This fieldname is the same in all languages.

Since the number of items in SplmtryData can be substantial an optional header can be passed to limit the data being returned in
this tag. By adding the "SplmtrydataMask" header with one or more of the values below, the data can be controlled.

* Mandate : All mandate related information (id, name, attribute values)
* Person : First and last name of the person
* Tracker : If a tracker was used for obtaining the contract
* Signature : All signatures ("SignerMethod\#","Signer\#","SignerPlace\#","SignerDate\#")
* SignatureAndPayload : All signatures including actual signature ("SignerMethod\#","Signer\#","SignerPlace\#","SignerDate\#","SignerPayload\#")

Possible values for SignatureMethod are :

* eid
* visa
* maestro
* mastercard
* plain
* mock
* print
* sms
* digiprint
* emachtiging

Note that this is a non-exhaustive list and is subject to new ones (but existing codes should always remain).

Since there may be more than one signature, the \# is followed by the number of the signer.
The "SignerPayload" is base64 encoded as it can contain signed xml's.

**Tag**: AdditionalFields

**Occurrences**: [0..1]

**Level**: 1

**Format**: See section 'SupplementaryData'

| Message Item | Tag	| Occurences | Format |
| ------------ | ------------- | ------------- |------------- |
| key | key | [1..1] | Max140Text |
| value | value | [1..1] | Max140Text |

**Usage**: field containing all available key=value pairs.

**XML Example**:

```xml
<SplmtryData>
    <Entry>
        <Key>MandateName</Key>
        <Value>BE - Online mandaat</Value>
    </Entry>
    <Entry>
        <Key>TemplateId</Key>
        <Value>1047</Value>
    </Entry>
</SplmtryData>
```

**JSON Example**:

```json
"SplmtryData": [
    {
        "Key": "SignerPlace#0",
        "Value": "Brussels"
    },
    {
        "Key": "SignerDate#0",
        "Value": "2015-06-22T21:00:00Z"
    }
]
```

## 5.  Occurences

The Ocrncs type consists of the following fields:

### SeqTp - Sequence Type or "Type of payment"

**Definition**: Identifies the underlying transaction sequence as either recurring or one-off.

**Tag**: SeqTp

**Occurrences**: [0..1]

**Level**: 1

**Type**: Code

One of the following _SequenceType2Code_ values must be used:

| Code | Name	| Definition |
| ------------ | ------------- | ------------- |
| OOFF | OneOff | Direct debit instruction where the debtor's authorisation is used to initiate one single direct debit transaction. |
| RCUR | Recurring | Direct debit instruction where the debtor's authorisation is used for regular direct debit transactions initiated by the creditor. |

**XML Example**:

```xml
<SeqTp>OOFF</SeqTp>
```

**JSON Example**:

```json
"SeqTp": "OOFF"
```

### Frqcy – Frequency

**Definition**: Regularity with which instructions are to be created and processed.

**Tag**: Frqcy

**Occurrences**: [0..1]

**Level**: 1

**Type**: Code

When this message item is present, one of the following _Frequency6Code_
values must be used:

| Code | Name	| Definition |
| ------------ | ------------- |------------- |
| ADHO | Adhoc | Event takes place on request or as necessary. |
| DAIL | Daily | Event takes place every day. |
| FRTN | Fortnightly | Event takes place every two weeks. |
| INDA | IntraDay | Event takes place several times a day. |
| MIAN | SemiAnnual | Event takes place every six months or two times a year. |
| MNTH | Monthly | Event takes place every month or once a month. |
| QURT | Quarterly | Event takes place every three months or four times a year. |
| WEEK | Weekly | Event takes place once a week. |
| YEAR | Annual | Event takes place every year or once a year. |

**XML Example**:

```xml
<Frqcy>YEAR</Frqcy>
```

**JSON Example**:

```json
"Frqcy": "YEAR"
```

### Drtn – Duration

**Definition**: Length of time for which the mandate remains valid.

**Tag**: Drtn

**Occurrences**: [0..1]

**Level**: 1

**Type**: This message item is composed of the following DatePeriodDetails1 element(s):

| Message Item | Tag	| Occurences | Format |
| ------------ | -------------| -------------| ------------- |
| FromDate | FrDt | [1..1] | YYYY-MM-DD |
| ToDate | ToDt | [0..1] | YYYY-MM-DD |

**Usage**: this is an optional field, indicating how long this mandate will be available at Twikey for debtors to sign up to. This field should not be used to decide on the actual validity of a mandate.

**XML Example**:

```xml
<Drtn>
	 <FrDt>2013-05-09</FrDt>
	 <ToDt/>
</Drtn>
```

**JSON Example**:

```json
"Drtn": {
  "FrDt": "2013-05-09",
  "ToDt": null
}
```

## 6.  Party

The Pty (Party) type consists of the following fields:

### Nm – Name


**Definition**: Name by which a party is known and which is usually used to identify that party. We recommend using the supplementary data (First name – Last name distinction) when information is provided to Twikey. Twikey will also deliver the first name & last name. This improves the data quality.

**Tag**: Nm

**Occurrences**: [0..1]

**Level**: 1

**Type**: Max140Text

**XML Example**:

	<Nm>John Doe</Nm>

**JSON Example**:

	"Nm": "John Doe"


### PstlAdr – PostalAddress

**Definition**: Information that locates and identifies a specific address, as defined by postal services.

**Tag**: PstlAdr

**Occurrences**: [0..1]

**Level**: 1

**Type**: This message item is composed of the following elements:

| Message Item | Tag	| Occurences | Format |
| ------------ | -------------| -------------| ------------- |
| AddressLine | AdrLine | [0..1] | StreetName + BuildingNumber **Format**: Max70Text |
| PostCode | PstCd | [0..1] | Identifier consisting of a group of letters and/or numbers that is added to a postal address to assist the sorting of mail. **Format**: Max16Text |
| TownName | TwnNm | [0..1] | Name of a built-up area, with defined boundaries, and a local government. **Format**: Max35Text |
| Country | Ctry | [0..1] | Nation with its own government. **Format**: [A-Z]{2,2} |

**Rule(s)**: The country code is checked against the list of country names obtained from the United Nations (ISO 3166, Alpha-2 code).

**XML Example**:

```xml
<PstlAdr>
    <AdrLine>Wildewoudstraat 10</AdrLine>
    <PstCd>1000</PstCd>
    <TwnNm>Brussel</TwnNm>
    <Ctry>BE</Ctry>
</PstlAdr>
```


**JSON Example**:

```json
"PstlAdr": {
    "AdrLine": "Wildewoudstraat 10",
    "PstCd": "1000",
    "TwnNm": "Brussel",
    "Ctry ": "BE"
}
```

### Id – Identification

**Definition**: Unique and unambiguous identification of a party.

**Tag**: Id

**Occurrences**: [0..1]

**Level**: 1

**Format**: Max35Text

**Usage:**

* InitgPty: Enterprise Number of the Creditor,
* Cdtr: Enterprise Number of the Creditor,,
* UltmtCdtr: Enterprise Number of the Ultimate Creditor,,
* Dbtr: Enterprise Number of the Debtor if the Debtor is a company, otherwise absent.
* UltmtDbtr: Enterprise Number of the Ultimate Debtor if the Ultimate Debtor is a company, otherwise absent.

**XML Example**:

	<Id>BE0533833797</Id>

**JSON Example**:

	"Id": "BE0533833797"


### CtryOfRes - CountryOfResidence

**Definition**: Country in which a person resides (the place of a person's home). In the case of a company, it is the country from which the affairs of that company are directed.

**Tag**: CtryOfRes

**Occurrences**: [0..1]

**Level**: 1

**Type**: CountryCode

**Format**: [A-Z]{2,2}

**Rule(s)**: Country

The code is checked against the list of country names obtained from the United
Nations (ISO 3166, Alpha-2 code).

**XML Example**:

	<CtryOfRes>be</CtryOfRes>

**JSON Example**:

	"CtryOfRes": "be"

### CtctDtls – ContactDetails


**Definition**: Unique and unambiguous identification of a party.

**Tag**: CtctDtls

**Occurrences**: [0..1]

**Level**: 1

**Type**: This message item is composed of the following elements:


| Message Item | Tag	| Occurences | Format |
| ------------ | ------------- | ------------- | ------------- |
| EmailAddress | EmailAdr | [0..1] | a valid email address. **Type**: Max2048Text |
| MobileNumber | MobNb | [0..1] | a mobile phone number. **For future use.** **Format:** \\+[0-9]{1,3}-[0-9()+\\-]{1,30} |


**Usage** :email address of the party.

**XML Example**:

```xml
<CtctDtls>
    <EmailAdr>[wim@kbc.be](mailto:wim@kbc.be)</EmailAdr>
    <MobNb>+32 476 40 11 46</MobNb>
</CtctDtls>
```

**JSON Example**:

```json
"CtctDtls": {
  "EmailAdr": "[wim@kbc.be](mailto:wim@bank.be)",
  "MobNb": "+32 476 40 11 46"
}
```

## 7.  Agent Type

The Agent type consists of the following fields:

### FinInstnId – FinancialInstitutionIdentification


**Definition**: Unique and unambiguous identification of a financial institution, as assigned under an internationally recognised or proprietary identification scheme.

**Tag**: FinInstnId

**Occurrences**: [0..1]

**Level**: 1

**Type**: This message item is composed of the following FinancialInstitutionIdentification8 element(s):


| Message Item | Tag	| Occurences | Format |
| ------------ | ------------- | ------------- | ------------- |
| BICFI | BICFI | [0..1] | Business identifier code (BIC) Format: [A-Z]{6,6}[A-Z2-9][A-NP-Z0-9]([A-Z0-9]{3,3}){0,1} |
| Name | Nm | [0..1] | Max140Text |


**XML Example**:

```xml
<FinInstnId>
  <BICFI>BBRUBEBB</BICFI>
  <Nm>ING BELGIUM NV/SA (FORMERLY BANK BRUSSELS LAMBERT SA), BRUSSELS</Nm>
</FinInstnId>
```

**JSON Example**:

```json
"FinInstnId": {
  "BICFI": "BBRUBEBB",
  "Nm": "ING BELGIUM NV/SA (FORMERLY BANK BRUSSELS LAMBERT SA),BRUSSELS"
}
```
