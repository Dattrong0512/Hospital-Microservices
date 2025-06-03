using Microsoft.AspNetCore.Identity;

namespace IDentityServices.Model
{
    public class User : IdentityUser<string>
    {
        
        public string ? RefreshToken { get; set; }
        public DateTime ? RefreshTokenExpiresAtUtc { get; set; }
        
    }

}
