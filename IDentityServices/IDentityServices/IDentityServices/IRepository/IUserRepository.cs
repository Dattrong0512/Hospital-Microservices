using IDentityServices.DTO;

namespace IDentityServices.IRepository
{
    public interface IUserRepository
    {
        Task<UserDto> RegisterUser(RegisterRequest registerRequest, bool isDoctor);


        Task<(string jwtToken, string refreshToken)> LoginUser(LoginRequest loginRequest);

        public Task<(string jwtToken, string refreshToken)> CreateAccesstokenFromRefreshToken(string RefreshToken);


    }
}
