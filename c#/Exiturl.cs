using System;
using System.Text;

//Given an exit url with the following value (defined in the template)
//
//    http:///website.com/{0}/{1}/{3}
//
//this would be expanded to
//
//    //https://www.website.com/SAMPLE11/ok/9AC1F6BF40A83197FE212F22A4D8790799BB2080EB75644D93FA2B8E9CBD6A9C
//
namespace twikey
{
    public class Exiturl
    {
        string websiteKey = "AEB24D30D4D6F3538413A11F9C81D680326B0AF59CACA0C7D94AC18B94AC7E60";

        static void Main(string[] args)
        {
            Console.WriteLine("Validity was : "+new Exiturl().checkHmacValidity("SAMPLE11","ok",null,"9AC1F6BF40A83197FE212F22A4D8790799BB2080EB75644D93FA2B8E9CBD6A9C"));
        }

        public bool checkHmacValidity(string document,string status,string token,string signature)
        {
			UTF8Encoding encoding = new UTF8Encoding();
            byte[] keyBytes = encoding.GetBytes(websiteKey);
            string payload = document+"/"+status;
            if(token != null)
            {
                payload += "/"+token;
            }
            byte[] messageBytes = encoding.GetBytes(payload);
            System.Security.Cryptography.HMACSHA256 cryptographer = new System.Security.Cryptography.HMACSHA256(keyBytes);
            byte[] bytes = cryptographer.ComputeHash(messageBytes);

			// 9AC1F6BF40A83197FE212F22A4D8790799BB2080EB75644D93FA2B8E9CBD6A9C
            Console.WriteLine(BitConverter.ToString(bytes).Replace("-", ""));
            return signature.Equals(BitConverter.ToString(bytes).Replace("-", ""));
        }
    }
}
