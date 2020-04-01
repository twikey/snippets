using System;
using System.Text;

namespace twikey
{
    public class WebHooks
    {
        string apiKey = "C0EEE955DD2E2BDE3D42AB2B7EAF668C92899E1B";

        protected void Page_Load(object sender, EventArgs e)
        {
            HttpRequest request = Request;
            string payload = request.QueryString.ToString();
            string decodedPayload = HttpUtility.UrlDecode(payload)
            checkHmacValidity(apiKey,decodedPayload,request.Header["X-SIGNATURE"]);
        }

        public bool checkHmacValidity(string apikey,string payload,string signature)
        {
            //public static string HmacSha256Digest(this string message, string secret)

			UTF8Encoding encoding = new UTF8Encoding();
            byte[] keyBytes = encoding.GetBytes(apiKey);
            byte[] messageBytes = encoding.GetBytes(payload);
            System.Security.Cryptography.HMACSHA256 cryptographer = new System.Security.Cryptography.HMACSHA256(keyBytes);
            byte[] bytes = cryptographer.ComputeHash(messageBytes);

			// 78EB6B5EAA49708FB90D27D2899FFFA175B0ABF454A2C1B3C324CC522A8D7185
            Console.WriteLine(BitConverter.ToString(bytes).Replace("-", ""));
            return signature.Equals(BitConverter.ToString(bytes).Replace("-", ""));
        }
    }
}
