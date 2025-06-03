using IDentityServices.Model;

namespace IDentityServices.Interface
{
    public interface IAuthTokenProcessor
    {
        Task<(string jwtToken, DateTime expiresAtUtc)> GenerateJwtToken(User user);
        string GenerateRefreshToken();
        void WriteAuthTokenAsHttpOnlyCookie(string cookieName, string token, DateTime expiration);
    }
}
