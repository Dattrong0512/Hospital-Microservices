using Azure.Core;
using IDentityServices.Context;
using IDentityServices.DTO;
using IDentityServices.Exceptions;
using IDentityServices.Interface;
using IDentityServices.IRepository;
using IDentityServices.Model;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Storage.Json;
using System.Security.Claims;
using System.Threading;

namespace IDentityServices.Repository
{
    public class UserRepository : IUserRepository
    {
        private readonly ApplicationDbContext _context;
        private readonly UserManager<User> _userManager;
        private readonly RoleManager<IdentityRole<string>> _roleManager;
        private readonly IAuthTokenProcessor _authTokenProcessor;



        public UserRepository(ApplicationDbContext context, UserManager<User> userManager, RoleManager<IdentityRole<string>> roleManager, IAuthTokenProcessor authTokenProcessor)
        {
            _context = context;
            _userManager = userManager;
            _roleManager = roleManager;
            _authTokenProcessor = authTokenProcessor;

        }

        public async Task<(string jwtToken, string refreshToken)> LoginUser(LoginRequest loginRequest)
        {
            var user = await _userManager.FindByNameAsync(loginRequest.Username);
            if (user == null || !await _userManager.CheckPasswordAsync(user, loginRequest.Password))
            {
                throw new LoginFailedException(loginRequest.Username);
            }

            var (jwtToken, expirationDateInUtc) = await _authTokenProcessor.GenerateJwtToken(user);


            var refreshToken = _authTokenProcessor.GenerateRefreshToken();
            var refreshTokenExpirationDateInUtc = DateTime.UtcNow.AddDays(90);

            user.RefreshToken = refreshToken;
            user.RefreshTokenExpiresAtUtc = refreshTokenExpirationDateInUtc;

            await _userManager.UpdateAsync(user);

            _authTokenProcessor.WriteAuthTokenAsHttpOnlyCookie("ACCESS_TOKEN", jwtToken, expirationDateInUtc);
            _authTokenProcessor.WriteAuthTokenAsHttpOnlyCookie("REFRESH_TOKEN", refreshToken, refreshTokenExpirationDateInUtc);

            var roles = await _userManager.GetRolesAsync(user);

            return (jwtToken, refreshToken);
        }

        public async Task<UserDto> RegisterUser(RegisterRequest registerRequest, bool isDoctor)
        {
            var userExists = await _context.Users.Where(us => us.PhoneNumber == registerRequest.PhoneNumber).FirstOrDefaultAsync();

            if (userExists != null)
            {
                throw new Exception("User has exists");
            }

            var user = new User {
                Id = Guid.NewGuid().ToString(),
                UserName = registerRequest.UserName,
            };

            user.PasswordHash = _userManager.PasswordHasher.HashPassword(user, registerRequest.PassWord);
            var result = await _userManager.CreateAsync(user);
            UserDto userReturn = new UserDto
            {
                ID = user.Id,
                UserName = user.UserName,
                PhoneNumber = registerRequest.PhoneNumber
            };

            if (isDoctor)
            {
                if (!await _roleManager.RoleExistsAsync("Doctor"))
                {
                    var role = new IdentityRole<string>
                    {
                        Id = Guid.NewGuid().ToString(),
                        Name = "Doctor",
                        NormalizedName = "DOCTOR" // Chuẩn hóa thủ công để đảm bảo
                    };
                    var roleResult = await _roleManager.CreateAsync(role);
                    if (!roleResult.Succeeded)
                    {
                        throw new Exception("Can't create User Role");
                    }
                }
                var addToRoleResult = await _userManager.AddToRoleAsync(user, "Doctor");
                if (!addToRoleResult.Succeeded)
                {
                    throw new Exception("Can't assign User Role to user: " + string.Join(", ", addToRoleResult.Errors.Select(e => e.Description)));
                }
            }
            else
            {
                if (!await _roleManager.RoleExistsAsync("Staff"))
                {
                    var role = new IdentityRole<string>
                    {
                        Id = Guid.NewGuid().ToString(),
                        Name = "Staff",
                        NormalizedName = "STAFF" // Chuẩn hóa thủ công để đảm bảo
                    };
                    var roleResult = await _roleManager.CreateAsync(role);
                    if (!roleResult.Succeeded)
                    {
                        throw new Exception("Can't create User Role");
                    }
                }
                var addToRoleResult = await _userManager.AddToRoleAsync(user, "Staff");
                if (!addToRoleResult.Succeeded)
                {
                    throw new Exception("Can't assign User Role to user: " + string.Join(", ", addToRoleResult.Errors.Select(e => e.Description)));
                }
            }
            return userReturn;
        }
    
    public async Task<(string jwtToken, string refreshToken)> CreateAccesstokenFromRefreshToken(string RefreshToken)
        {
            if (string.IsNullOrEmpty(RefreshToken))
            {
                throw new Exception("Refresh token is missing.");
            }

            var user = await _context.Users.FirstOrDefaultAsync(u => u.RefreshToken == RefreshToken);
            if (user == null)
            {
                throw new Exception("Unable to retrieve user for refresh token");
            }

            if (user.RefreshTokenExpiresAtUtc < DateTime.UtcNow)
            {
                throw new Exception("Refresh token is expired.");
            }

            var (jwtToken, expirationDateInUtc) = await _authTokenProcessor.GenerateJwtToken(user);
            var refreshToken = _authTokenProcessor.GenerateRefreshToken();
            var refreshTokenExpirationDateInUtc = DateTime.UtcNow.AddDays(90);


            user.RefreshToken = refreshToken;
            user.RefreshTokenExpiresAtUtc = refreshTokenExpirationDateInUtc;
            await _userManager.UpdateAsync(user);

            _authTokenProcessor.WriteAuthTokenAsHttpOnlyCookie("ACCESS_TOKEN", jwtToken, expirationDateInUtc);
            _authTokenProcessor.WriteAuthTokenAsHttpOnlyCookie("REFRESH_TOKEN", refreshToken, refreshTokenExpirationDateInUtc);

            return (jwtToken, refreshToken);
        }

    }
}
