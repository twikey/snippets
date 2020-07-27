package com.twikey;

import org.json.JSONArray;
import org.json.JSONObject;
import org.json.JSONTokener;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.time.LocalDate;
import java.util.HashMap;
import java.util.Map;

/**
 * Eg. usage or see unittests for more info
 *
 * <pre></pre>
 * String apiKey = "87DA7055C5D18DC5F3FC084F9F208AB335340977"; // found in https://www.twikey.com/r/admin#/c/settings/api
 * long ct = 1420; // found @ https://www.twikey.com/r/admin#/c/template
 * TwikeyAPI api = new TwikeyAPI(apiKey,true);
 * System.out.println(api.createInvoice(ct,customer,invoiceDetails));
 * System.out.println(api.inviteMandate(ct,null,new HashMap<>()));
 * </pre>
 */
public class TwikeyAPI {

    private static final String UTF_8 = "UTF-8";

    private static final String USER_HEADER = "twikey/java-1.0";
    private static final String PROD_ENVIRONMENT = "https://api.twikey.com/creditor";
    private static final String TEST_ENVIRONMENT = "https://api.beta.twikey.com/creditor";

    private static final long MAX_SESSION_AGE = 23 * 60 * 60 * 60; // max 1day, but use 23 to be safe

    private final String apiKey;

    private final String endpoint;
    private long lastLogin;
    private String sessionToken;

    /**
     * @param apikey API key
     * @param test Use the test environment
     */
    public TwikeyAPI(String apikey, boolean test){
        this.apiKey = apikey;
        endpoint = test ? TEST_ENVIRONMENT : PROD_ENVIRONMENT;
    }

    /**
     * @param apikey API key
     */
    public TwikeyAPI(String apikey){
        this(apikey,false);
    }

    private String getSessionToken() throws IOException, UnauthenticatedException {
        if((System.currentTimeMillis() - lastLogin) > MAX_SESSION_AGE){
            URL myurl = new URL(endpoint);
            HttpURLConnection con = (HttpURLConnection)myurl.openConnection();
            con.setRequestMethod("POST");
            con.setRequestProperty("User-Agent", USER_HEADER);
            con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
            con.setDoOutput(true);
            con.setDoInput(true);

            try(DataOutputStream output = new DataOutputStream(con.getOutputStream())){
                output.writeBytes(String.format("apiToken=%s", apiKey));
                output.flush();
            }

            sessionToken = con.getHeaderField("Authorization");
            con.disconnect();

            if(sessionToken != null){
                lastLogin = System.currentTimeMillis();
            }
            else {
                lastLogin = 0;
                throw new UnauthenticatedException();
            }
        }

        return sessionToken;
    }

    /**
     * <table>
     * <thead>
     * <tr>
     * <th>Name</th>
     * <th>Description</th>
     * <th>Required</th>
     * <th>Type</th>
     * </tr>
     * </thead>
     * <tbody>
     * <tr>
     * <td>l</td>
     * <td>Language (en/fr/nl/de/pt/es/it)</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>iban</td>
     * <td>International Bank Account Number of the debtor</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>bic</td>
     * <td>Bank Identifier Code of the IBAN</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>mandateNumber</td>
     * <td>Mandate Identification number (if not generated)</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>contractNumber</td>
     * <td>The contract number which can override the one defined in the template.</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>campaign</td>
     * <td>Campaign to include this url in</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>reminderDays</td>
     * <td>Send a reminder if contract was not signed after number of days</td>
     * <td>No</td>
     * <td>number</td>
     * </tr>
     * <tr>
     * <td>sendInvite</td>
     * <td>Send out invite email directly</td>
     * <td>No</td>
     * <td>boolean</td>
     * </tr>
     * </tbody></table>
     * @param ct Template to use can be found @ https://www.twikey.com/r/admin#/c/template
     * @param customer Customer details
     * @param mandateDetails Map containing any of the parameters in the above table
     * @return Url to redirect the customer to or to send in an email
     */
    public String inviteMandate(long ct, Customer customer, Map<String,String> mandateDetails) throws IOException, UserException {
        Map<String, String> params = new HashMap<>(mandateDetails);
        params.put("ct", String.valueOf(ct));
        if(customer != null){
            params.put("customerNumber",customer.ref);
            params.put("email",customer.email);
            params.put("firstname",customer.firstname);
            params.put("lastname",customer.lastname);
            params.put("l",customer.lang);
            params.put("address",customer.street);
            params.put("city",customer.city);
            params.put("zip",customer.zip);
            params.put("country",customer.country);
            params.put("mobile",customer.mobile);
            params.put("companyName",customer.companyName);
            params.put("coc",customer.coc);
        }

        URL myurl = new URL(endpoint + "/prepare");
        HttpURLConnection con = (HttpURLConnection)myurl.openConnection();
        con.setRequestMethod("POST");
        con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        con.setRequestProperty("User-Agent", USER_HEADER);
        con.setRequestProperty("Authorization", getSessionToken());
        con.setDoOutput(true);
        con.setDoInput(true);

        try(DataOutputStream output = new DataOutputStream(con.getOutputStream())){
            output.writeBytes(getPostDataString(params));
            output.flush();
        }

        int responseCode=con.getResponseCode();
        if(responseCode == 200){
            try (BufferedReader br = new BufferedReader(new InputStreamReader(con.getInputStream()))) {
            /* {
              "mndtId": "COREREC01",
              "url": "http://twikey.to/myComp/ToYG",
              "key": "ToYG"
            } */
                JSONObject json = new JSONObject(new JSONTokener(br));
                return json.getString("url");
            }
        }
        else {
            String apiError = con.getHeaderField("ApiError");
            throw new UserException(apiError);
        }
    }

    /**
     * <table>
     * <thead>
     * <tr>
     * <th>Name</th>
     * <th>Description</th>
     * <th>Required</th>
     * <th>Type</th>
     * </tr>
     * </thead>
     * <tbody><tr>
     * <td>id</td>
     * <td>UUID of the invoice (optional)</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>number</td>
     * <td>Invoice number</td>
     * <td>Yes</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>title</td>
     * <td>Message to the debtor</td>
     * <td>Yes</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>remittance</td>
     * <td>Payment message, if empty then title will be used</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>amount</td>
     * <td>Amount to be billed</td>
     * <td>Yes</td>
     * <td>string</td>
     * </tr>
     * <tr>
     * <td>date</td>
     * <td>Invoice date</td>
     * <td>Yes</td>
     * <td>date</td>
     * </tr>
     * <tr>
     * <td>duedate</td>
     * <td>Due date</td>
     * <td>Yes</td>
     * <td>date</td>
     * </tr>
     * <tr>
     * <td>pdf</td>
     * <td>Base64 encoded document</td>
     * <td>No</td>
     * <td>string</td>
     * </tr>
     * </tbody></table>
     *
     * @param ct Template to use can be found @ https://www.twikey.com/r/admin#/c/template
     * @param customer Customer details
     * @param invoiceDetails Details specific to the invoice
     * @return Url of the invoice normally https://merchant.twikey.com/nl/invoice.html?<id>
     */
    public String createInvoice(long ct, Customer customer, Map<String,String> invoiceDetails) throws IOException, UserException {

        JSONObject customerAsJson = new JSONObject()
                .put("customerNumber",customer.ref)
                .put("email",customer.email)
                .put("firstname",customer.firstname)
                .put("lastname",customer.lastname)
                .put("l",customer.lang)
                .put("address",customer.street)
                .put("city",customer.city)
                .put("zip",customer.zip)
                .put("country",customer.country)
                .put("mobile",customer.mobile)
                .put("companyName",customer.companyName)
                .put("coc",customer.coc);

        JSONObject invoice = new JSONObject()
            .put("customer",customerAsJson)
            .put("date",invoiceDetails.getOrDefault("date", LocalDate.now().toString()))
            .put("duedate",invoiceDetails.getOrDefault("date", LocalDate.now().plusMonths(1).toString()))
            .put("ct", ct);

        for (Map.Entry<String, String> entry : invoiceDetails.entrySet()) {
            invoice.put(entry.getKey(),entry.getValue());
        }

        URL myurl = new URL(endpoint + "/invoice");
        HttpURLConnection con = (HttpURLConnection)myurl.openConnection();
        con.setRequestMethod("POST");
        con.setRequestProperty("Content-Type", "application/json");
        con.setRequestProperty("User-Agent", USER_HEADER);
        con.setRequestProperty("Authorization", getSessionToken());
        con.setDoOutput(true);
        con.setDoInput(true);

        try(DataOutputStream output = new DataOutputStream(con.getOutputStream())){
            output.writeBytes(invoice.toString());
            output.flush();
        }

        int responseCode=con.getResponseCode();
        if(responseCode == 200){
            try (BufferedReader br = new BufferedReader(new InputStreamReader(con.getInputStream()))) {
            /* {
              "id": "fec44175-b4fe-414c-92aa-9d0a7dd0dbf2",
              "number": "Inv20200001",
              "title": "Invoice July",
              "ct": 1988,
              "amount": "100.00",
              "date": "2020-01-31",
              "duedate": "2020-02-28",
              "status": "BOOKED",
              "url": "https://yourpage.beta.twikey.com/invoice.html?fec44175-b4fe-414c-92aa-9d0a7dd0dbf2"
          }*/
                JSONObject json = new JSONObject(new JSONTokener(br));
                return json.getString("url");
            }
        }
        else {
            String apiError = con.getHeaderField("ApiError");
            throw new UserException(apiError);
        }
    }

    public interface MandateCallback {
        void newMandate(JSONObject newMandate);
        void updatedMandate(JSONObject updatedMandate);
        void cancelledMandate(JSONObject cancelledMandate);
    }

    public interface InvoiceCallback {
        void invoice(JSONObject updatedInvoice);
    }

    /**
     * Get updates about all mandates (new/updated/cancelled)
     * @param mandateCallback Callback for every change
     * @param since Optional reset (null = since the last call)
     * @throws IOException When a network issue happened
     * @throws UserException When there was an issue while retrieving the mandates (eg. invalid apikey)
     */
    public void getUpdatedMandates(MandateCallback mandateCallback, String since) throws IOException, UserException {
        URL myurl = new URL(endpoint + "/mandate");

        HttpURLConnection con = (HttpURLConnection)myurl.openConnection();
        con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        con.setRequestProperty("User-Agent", USER_HEADER);
        con.setRequestProperty("Authorization", getSessionToken());

        if (since != null) {
            con.setRequestProperty("X-Reset", since);
        }

        int responseCode=con.getResponseCode();
        if(responseCode == 200){
            try (BufferedReader br = new BufferedReader(new InputStreamReader(con.getInputStream()))) {
                JSONObject json = new JSONObject(new JSONTokener(br));

                JSONArray messagesArr = json.getJSONArray("Messages");
                if (!messagesArr.isEmpty()) {
                    for (int i = 0 ; i < messagesArr.length(); i++) {
                        JSONObject obj = messagesArr.getJSONObject(i);
                        if (obj.has("CxlRsn")) {
                            mandateCallback.cancelledMandate(obj);
                        } else if (obj.has("AmdmntRsn")) {
                            mandateCallback.updatedMandate(obj);
                        } else {
                            mandateCallback.newMandate(obj);
                        }
                    }
                }
            }
        }
        else {
            String apiError = con.getHeaderField("ApiError");
            throw new UserException(apiError);
        }
    }

    /**
     * Get updates about all mandates (new/updated/cancelled)
     * @param invoiceCallback Callback for every change
     * @param since Optional reset (null = since the last call)
     * @throws IOException When a network issue happened
     * @throws UserException When there was an issue while retrieving the mandates (eg. invalid apikey)
     */
    public void getUpdatedInvoices(InvoiceCallback invoiceCallback, String since) throws IOException, UserException{
        URL myurl = new URL(endpoint + "/invoice");

        HttpURLConnection con = (HttpURLConnection) myurl.openConnection();
        con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        con.setRequestProperty("User-Agent", USER_HEADER);
        con.setRequestProperty("Authorization", getSessionToken());

        if (since != null) {
            con.setRequestProperty("X-Reset", since);
        }

        int responseCode=con.getResponseCode();
        if(responseCode == 200){
            try (BufferedReader br = new BufferedReader(new InputStreamReader(con.getInputStream()))) {
                JSONObject json = new JSONObject(new JSONTokener(br));

                JSONArray invoicesArr = json.getJSONArray("Invoices");
                if (!invoicesArr.isEmpty()) {
                    for (int i = 0; i < invoicesArr.length(); i++) {
                        JSONObject obj = invoicesArr.getJSONObject(i);
                        invoiceCallback.invoice(obj);
                    }
                }
            }
        }
        else {
            String apiError = con.getHeaderField("ApiError");
            throw new UserException(apiError);
        }
    }

    private static String getPostDataString(Map<String, String> params)  {
        try{
            StringBuilder result = new StringBuilder();
            boolean first = true;
            for(Map.Entry<String, String> entry : params.entrySet()){
                if (first)
                    first = false;
                else
                    result.append("&");

                result.append(URLEncoder.encode(entry.getKey(), UTF_8));
                result.append("=");
                result.append(URLEncoder.encode(entry.getValue(), UTF_8));
            }
            return result.toString();
        } catch (UnsupportedEncodingException e) {
            // should not happen on UTF8
            throw new RuntimeException(e);
        }
    }

    // No getters for briefness
    public static class Customer {
        private String lastname;
        private String firstname;
        private String email;
        private String lang;
        private String mobile;
        private String street;
        private String city;
        private String zip;
        private String country;
        private String ref;
        private String companyName;
        private String coc;

        public Customer(){}

        public Customer setLastname(String lastname) {
            this.lastname = lastname;
            return this;
        }

        public Customer setFirstname(String firstname) {
            this.firstname = firstname;
            return this;
        }

        public Customer setEmail(String email) {
            this.email = email;
            return this;
        }

        public Customer setLang(String lang) {
            this.lang = lang;
            return this;
        }

        public Customer setMobile(String mobile) {
            this.mobile = mobile;
            return this;
        }

        public Customer setStreet(String street) {
            this.street = street;
            return this;
        }

        public Customer setCity(String city) {
            this.city = city;
            return this;
        }

        public Customer setZip(String zip) {
            this.zip = zip;
            return this;
        }

        public Customer setCountry(String country) {
            this.country = country;
            return this;
        }

        public Customer setRef(String ref) {
            this.ref = ref;
            return this;
        }

        public Customer setCompanyName(String companyName) {
            this.companyName = companyName;
            return this;
        }

        public Customer setCoc(String coc) {
            this.coc = coc;
            return this;
        }
    }

    public static class UserException extends Throwable {
        public UserException(String apiError) {
            super(apiError);
        }
    }

    public static class UnauthenticatedException extends UserException {
        public UnauthenticatedException(){
            super("Not authenticated");
        }
    }
}
