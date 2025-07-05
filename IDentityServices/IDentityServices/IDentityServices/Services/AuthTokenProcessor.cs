using IDentityServices.Helper;
using IDentityServices.Interface;
using IDentityServices.Model;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Options;
using Microsoft.IdentityModel.Tokens;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Security.Cryptography;
using System.Text;
using System.Net.Http;
using System.Text.Json;

namespace IDentityServices.Services
{
    public class AuthTokenProcessor : IAuthTokenProcessor
    {
        private readonly JwtOptions _jwtOptions;
        private readonly IHttpContextAccessor _contextAccessor;
        private readonly UserManager<User> _userManager;
        private readonly HttpClient _httpClient;
        private readonly ILogger<AuthTokenProcessor> _logger;


        public AuthTokenProcessor(IOptions<JwtOptions> jwtOptions, IHttpContextAccessor ContextAccessor, UserManager<User> userManager, ILogger<AuthTokenProcessor> logger)
        {
            _jwtOptions = jwtOptions.Value;
            _contextAccessor = ContextAccessor;
            _userManager = userManager;
            _httpClient = new HttpClient();
            _logger = logger;
        }

        // Hàm lấy secret từ Kong Gateway
        private async Task<(string,string)> GetSecretFromKong(string consumerId)
        {
            string url = $"http://138.197.142.55:8001/consumers/{consumerId}/jwt";
            _logger.LogInformation(url);
            var response = await _httpClient.GetAsync(url);

            if (response.StatusCode != System.Net.HttpStatusCode.OK)
            {
                throw new HttpRequestException($"Không thể lấy secret từ Kong, mã lỗi: {response.StatusCode}");
            }

            var responseContent = await response.Content.ReadAsStringAsync();
            using var jsonDoc = JsonDocument.Parse(responseContent);
            var root = jsonDoc.RootElement;

            if (!root.TryGetProperty("data", out var dataElement) || !dataElement.EnumerateArray().Any())
            {
                throw new HttpRequestException("Không tìm thấy chứng chỉ JWT cho consumer này.");
            }

            var secret = dataElement[0].GetProperty("secret").GetString();
            var issuer = dataElement[0].GetProperty("key").GetString();
            _logger.LogInformation(secret, issuer);
            if (string.IsNullOrEmpty(secret))
            {
                throw new HttpRequestException("Secret không hợp lệ hoặc rỗng.");
            }

            return (secret,issuer);
        }

        public async Task<(string jwtToken, DateTime expiresAtUtc)> GenerateJwtToken(User user)
        {
            // Lấy consumerId (giả sử bạn có cách lấy consumerId từ user, thay đổi theo logic của bạn)
            string consumerId = "token_server"; // Thay bằng consumerId thực tế của user
            var (secret, issuer) = await GetSecretFromKong(consumerId);

            _logger.LogInformation(secret);
            // Sử dụng secret từ Kong để ký token
            var signingKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(secret));
            var credentials = new SigningCredentials(signingKey, SecurityAlgorithms.HmacSha256);

            // Kiểm tra vai trò của user
            ICollection<string> checkRole = await _userManager.GetRolesAsync(user);
            if (checkRole.Count() == 0)
            {
                return (null, DateTime.Now);
            }

            string role;
            if (checkRole.Contains("Doctor"))
            {
                role = "Doctor";
            }
            else if (checkRole.Contains("Staff"))
            {
                role = "Staff";
            }
            else
            {
                role = "";
            }

            // Tạo claims, thêm "iss" để Kong nhận diện consumer
            var claims = new List<Claim>
            {
                new Claim(JwtRegisteredClaimNames.Sub, user.Id),
                new Claim(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString()),
                new Claim("Role", role),
                new Claim(JwtRegisteredClaimNames.Name, user.UserName),
                new Claim(JwtRegisteredClaimNames.Iss, issuer) // Thêm iss claim, sử dụng consumerId
            };

            // Tính thời gian hết hạn
            var expires = DateTime.UtcNow.AddMinutes(_jwtOptions.ExpirationTimeInMinutes);
            _logger.LogInformation(expires.ToString());
            // Tạo token
            var token = new JwtSecurityToken(
                audience: _jwtOptions.Audience,
                claims: claims,
                expires: expires,
                signingCredentials: credentials
            );

            var jwtToken = new JwtSecurityTokenHandler().WriteToken(token);
            return (jwtToken, expires);
        }

        public string GenerateRefreshToken()
        {
            var randomNumber = new byte[64];
            using var rng = RandomNumberGenerator.Create();
            rng.GetBytes(randomNumber);
            return Convert.ToBase64String(randomNumber);
        }

        public void WriteAuthTokenAsHttpOnlyCookie(string cookieName, string token, DateTime expiration)
        {
            _contextAccessor.HttpContext.Response.Cookies.Append(cookieName, token, new CookieOptions
            {
                HttpOnly = false,
                Expires = expiration,
                IsEssential = true,
                Secure = true,
                SameSite = SameSiteMode.None
            });
        }
    }
}