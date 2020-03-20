using System;
using System.Net.Http;
using System.Text.Json;
using System.Text.Json.Serialization;
using System.Threading.Tasks;
using System.Diagnostics;
using System.IO;
using System.Text;

namespace twikey
{
    class InvoiceImport
    {
        static void Main(string[] args)
        // static async Task Main(string[] args)
        {
            var invoiceImport = new InvoiceImport();

            //Byte[] bytes = File.ReadAllBytes("INV123.pdf");
            //String file = Convert.ToBase64String(bytes);

            var customer = new Customer();
            customer.code = "124";
            customer.email = "koen+124@twikey.com";
            customer.firstname = "Koen";
            customer.lastname = "Serry";
            customer.locale = "nl_BE";
            customer.phone = "+3293954500";
            customer.street = "Derbystraat 43";
            customer.city = "Gent";
            customer.zip = "9051";
            customer.country = "BE";
            customer.company = "Twikey";
            customer.coc = "0533800797";

            var invoice = new Invoice();
            //invoice.id = // interne referentie als deze een uuid is
            invoice.number = "1234"; // Factuurnummer
            invoice.title = "Factuur 1234"; // Titel voor de klant
            invoice.remittance = "RF8504486696262077669"; // betaal specificatie
            invoice.amount = 1000;
            invoice.template = 1; // template number per merchant default = first sdd template
            invoice.date = DateTime.Today;
            invoice.duedate = DateTime.Today.AddDays(30);
            invoice.customer = customer;
            // invoice.pdf = file;

            var task = invoiceImport.sendInvoice(invoice);
            task.Wait();
        }

        public async Task sendInvoice(Invoice invoice)
        {
            using (HttpClient httpClient = new HttpClient())
            {
                httpClient.DefaultRequestHeaders.Add("Authorization","Bearer C0EEE955DD2E2BDE3D42FB2B7EAF668C92899E1B");
                httpClient.DefaultRequestHeaders.Add("Accept","application/json");
                httpClient.DefaultRequestHeaders.Add("Accept-Language","nl");
                httpClient.DefaultRequestHeaders.Add("User-Agent","d-basics/customer");
                httpClient.BaseAddress = new Uri("https://api.beta.twikey.com");
                // production = https://api.twikey.com
                try
                {
                    var jsonString = JsonSerializer.Serialize<Invoice>(invoice);
                    //Console.WriteLine(jsonString);
                    var content = new StringContent(jsonString, Encoding.UTF8, "application/json");
                    var response = await httpClient.PostAsync("/v2/documents/invoice", content);
                    var responseString = await response.Content.ReadAsStringAsync();
                    if (response.IsSuccessStatusCode)
                    {
                        Console.WriteLine("OK "+responseString);
                    }
                    else {
                        Console.WriteLine("NOK "+responseString);
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine(ex);
                }
            }
        }
    }

    public class Invoice
    {
        public int amount { get; set; }
        public int template { get; set; }

        [JsonPropertyName("ref")]
        public string number { get; set; }

        public string doctype = "invoice";
        public string status = "booked";
        public Customer customer { get; set; }

        [JsonIgnore]
        public DateTime date { get; set; }

        [JsonPropertyName("date")]
        public string dateValue
        {
            get
            {
                return this.date.ToString("yyyy-MM-dd");
            }
        }

        [JsonIgnore]
        public DateTime duedate { get; set; }

        [JsonPropertyName("duedate")]
        public string duedateValue
        {
            get
            {
                return this.duedate.ToString("yyyy-MM-dd");
            }
        }

        public string remittance { get; set; }
        public string title { get; set; }
        public string pdf { get; set; }
    }

    public class Customer
    {
        public string code { get; set; }
        public string email { get; set; }
        public string firstname { get; set; }
        public string lastname { get; set; }
        public string locale { get; set; }
        public string phone { get; set; }
        public string street { get; set; }
        public string city { get; set; }
        public string zip { get; set; }
        public string country { get; set; }
        public string company { get; set; }
        public string coc { get; set; }
    }
}
