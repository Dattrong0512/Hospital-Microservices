namespace IDentityServices.Helper
{
    public class JwtOptions
    {
        public const string JwtOptionKey = "JwtOptions";
        public string Secret { get; set; }
        public string Issuer { get; set; }
        public string Audience { get; set; }
        public int ExpirationTimeInMinutes { get; set; }
    }
}
