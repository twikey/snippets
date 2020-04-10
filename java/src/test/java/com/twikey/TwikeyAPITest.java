package com.twikey;

import org.junit.Before;
import org.junit.Test;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

public class TwikeyAPITest {

    private TwikeyAPI.Customer customer;

    @Before
    public void createCustomer(){
        customer = new TwikeyAPI.Customer()
            .setRef("customerNum123")
            .setEmail("no-reply@example.com")
            .setFirstname("Twikey")
            .setLastname("Support")
            .setStreet("Derbystraat 43")
            .setCity("Gent")
            .setZip("9000")
            .setCountry("BE")
            .setLang("nl")
            .setMobile("32498665995");
    }

    @Test
    public void testInviteMandateWithoutCustomerDetails() throws IOException, TwikeyAPI.UserException {
        String apiKey = "87DA7055C5D18DC5F3FC084F9F2085B335340977"; // found in https://www.twikey.com/r/admin#/c/settings/api
        long ct = 1420; // found @ https://www.twikey.com/r/admin#/c/template
        TwikeyAPI api = new TwikeyAPI(apiKey,true);
        System.out.println(api.inviteMandate(ct,null,new HashMap<>()));
    }

    @Test
    public void testInviteMandateCustomerDetails() throws IOException, TwikeyAPI.UserException {
        String apiKey = "87DA7055C5D18DC5F3FC084F9F2085B335340977"; // found in https://www.twikey.com/r/admin#/c/settings/api
        long ct = 1420; // found @ https://www.twikey.com/r/admin#/c/template
        TwikeyAPI api = new TwikeyAPI(apiKey,true);
        System.out.println(api.inviteMandate(ct,customer,new HashMap<>()));
    }

    @Test
    public void testCreateInvoice() throws IOException, TwikeyAPI.UserException {
        String apiKey = "87DA7055C5D18DC5F3FC084F9F2085B335340977"; // found in https://www.twikey.com/r/admin#/c/settings/api
        long ct = 1420; // found @ https://www.twikey.com/r/admin#/c/template
        TwikeyAPI api = new TwikeyAPI(apiKey,true);

        Map<String, String> invoiceDetails = new HashMap<>();
        invoiceDetails.put("number", "Invss123");
        invoiceDetails.put("title", "Invoice April");
        invoiceDetails.put("remittance", "123456789123");
        invoiceDetails.put("amount", "10.90");
        invoiceDetails.put("date", "2020-03-20");
        invoiceDetails.put("duedate","2020-04-28");
        System.out.println(api.createInvoice(ct,customer,invoiceDetails));
    }
}
