using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;
using System.Web;

namespace TestWebApp
{
    public static class TwikeyAPI
    {
        private const string baseAddress = "https://api.twikey.com";
        private const string SALT = "own";
        private const string API_TOKEN = "<your token>"; // Twikey settings > Api > API Token
        private const string PRIVATE_KEY = "your key"; // Twikey settings > Api > Private Key

//      Pre C# 4.5
//        static void Main(string[] args)
//        {
//            var otp_calculated = calculateOTP();
//            var payload = "apiToken=" + API_TOKEN + "&otp=" + otp_calculated;
//            byte[] postData = Encoding.UTF8.GetBytes (payload);
//            var request = WebRequest.Create(baseAddress + "/creditor");
//            request.Method = "POST";
//            request.ContentType = "application/x-www-form-urlencoded";
//            request.ContentLength = postData.Length;
//            Stream requestStream = request.GetRequestStream();
//            requestStream.Write(postData, 0, postData.Length);
//            requestStream.Close();
//
//            var response = (HttpWebResponse)request.GetResponse();
//            var responseString = new StreamReader(response.GetResponseStream()).ReadToEnd();
//            Console.WriteLine("Response was : "+responseString);
//        }

        static void Main(string[] args)
        {
            using (var client = new HttpClient())
            {
                client.BaseAddress = new Uri(baseAddress);
                client.DefaultRequestHeaders.Accept.Clear();
                client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/xml"));
                var content = new FormUrlEncodedContent(new[]
                {
                    new KeyValuePair<string, string>("apiToken", API_TOKEN),
                    //new KeyValuePair<string, string>("otp", ""+calculateOTP()) // optional
                });

                content.Headers.ContentType = new MediaTypeHeaderValue("application/x-www-form-urlencoded");
                var result = client.PostAsync("/creditor", content).Result;
                string resultContent = result.Content.ReadAsStringAsync().Result;
                var authorization = result.Headers.GetValues("Authorization").ToList();
                if (authorization.Count == 1)
                {
                    var auth = authorization[0]; // 24 uur actief
                    if (!string.IsNullOrEmpty(auth))
                    {
                        client.DefaultRequestHeaders.Add("Authorization", auth);

                        content = new FormUrlEncodedContent(new[]
                        {
                            new KeyValuePair<string, string>("ct", "1313"),
                            new KeyValuePair<string, string>("firstname", "Koen"),
                            new KeyValuePair<string, string>("lastnamename", "Serry"),
                            new KeyValuePair<string, string>("email", "john@doe.com"),
                            new KeyValuePair<string, string>("firstname", "John"),
                            new KeyValuePair<string, string>("lastname", "Doe"),
                            new KeyValuePair<string, string>("l", "nl"),
                            new KeyValuePair<string, string>("address", "Abbey road"),
                            new KeyValuePair<string, string>("city", "Liverpool"),
                            new KeyValuePair<string, string>("zip", "1526"),
                            new KeyValuePair<string, string>("country", "BE"),
                            new KeyValuePair<string, string>("mobile", ""),
                            new KeyValuePair<string, string>("companyName", ""),
                            new KeyValuePair<string, string>("form", ""),
                            new KeyValuePair<string, string>("vatno", "")
                        });
                        content.Headers.ContentType = new MediaTypeHeaderValue("application/x-www-form-urlencoded");
                        var result2 = client.PostAsync("/creditor/prepare", content).Result;

                        var resultContent = result2.Content.ReadAsStringAsync().Result;

                        var doc = new XmlDocument();
                        doc.LoadXml(resultContent);
                        var rootNode = doc.SelectSingleNode("*");
                        if (rootNode != null && doc.DocumentElement != null) {
                            var node = doc.DocumentElement.SelectSingleNode("/PrepareResponse/url");
                            if (node != null && !string.IsNullOrEmpty(node.InnerText)){
                                Browser.Navigate(node.InnerText);
                            }
                        }
                    }
                }
            }
        }

        private static long calculateOTP() {
            byte[] _salt = Encoding.UTF8.GetBytes(SALT);
            byte[] _private_key = StringToByteArray(PRIVATE_KEY);
            byte[] key = new byte[_salt.Length + _private_key.Length];
            System.Buffer.BlockCopy(_salt, 0, key, 0, _salt.Length);
            System.Buffer.BlockCopy(_private_key, 0, key, _salt.Length, _private_key.Length);

            HMACSHA256 hmac = new HMACSHA256(key);
            hmac.Initialize();

            DateTime UNIX_EPOCH = new DateTime(1970, 1, 1, 0, 0, 0, DateTimeKind.Utc);
            byte[] counter = BitConverter.GetBytes((long)((DateTime.UtcNow - UNIX_EPOCH).TotalSeconds / 30));
            if (BitConverter.IsLittleEndian)
                Array.Reverse(counter);

            byte[] hash = hmac.ComputeHash(counter);
            int offset = hash[19] & 0xf;
            long v = (hash[offset]     & 0x7f) << 24 |
                     (hash[offset + 1] & 0xff) << 16 |
                     (hash[offset + 2] & 0xff) << 8  |
                     (hash[offset + 3] & 0xff);
            return v % 100000000; // last 8 digits are important
        }

        private static byte[] StringToByteArray(string hex) {
            return Enumerable.Range(0, hex.Length)
                             .Where(x => x % 2 == 0)
                             .Select(x => Convert.ToByte(hex.Substring(x, 2), 16))
                             .ToArray();
        }

        private static string DecryptAccountInfo(string key, string payload)
        {
            MD5 md5Hash = MD5.Create();
            byte[] keyBytes = md5Hash.ComputeHash(Encoding.UTF8.GetBytes(key));

            byte[] bytesPayload = hexToBytes(payload);

            string accountInfo;
            using (AesManaged AES = new AesManaged())
            {
                AES.Key = keyBytes;
                AES.IV = keyBytes;
                AES.Mode = CipherMode.CBC;
                AES.Padding = PaddingMode.PKCS7;
                ICryptoTransform decrypto = AES.CreateDecryptor();
                accountInfo = Encoding.UTF8.GetString(decrypto.TransformFinalBlock(bytesPayload, 0, bytesPayload.Length));
            }
            return accountInfo;
        }


        private static byte[] hexToBytes(string hex)
        {
            return Enumerable.Range(0, hex.Length)
                             .Where(x => x % 2 == 0)
                             .Select(x => Convert.ToByte(hex.Substring(x, 2), 16))
                             .ToArray();
        }
    }
}
