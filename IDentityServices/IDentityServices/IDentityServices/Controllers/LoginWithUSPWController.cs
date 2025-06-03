using IDentityServices.DTO;
using IDentityServices.Exceptions;
using IDentityServices.IRepository;
using Microsoft.AspNetCore.Mvc;

namespace IDentityServices.Controllers
{
    [ApiVersion("0")]
    [Route("api/v{version:apiVersion}")]
    [ApiController]
    public class LoginWithUSPWController : ControllerBase
    {
        private readonly ILogger<LoginWithUSPWController> _logger;
        private readonly IUserRepository _repository;

        public LoginWithUSPWController(ILogger<LoginWithUSPWController> logger, IUserRepository repository)
        {
            _logger = logger;
            _repository = repository;
        }
        [HttpGet("/Hello")]
        public IActionResult Hello()
        {
            return Ok("Hello, this is the IDentityServices API!");
        }
        /// <summary>
        /// Registers a new user account for doctor.
        /// </summary>
        /// <remarks>
        /// This API allows the creation of a new user account with the provided registration details.
        ///
        /// **Request:**
        /// The request body must contain the user information:
        /// - **UserName**: The username for the account.
        /// - **PhoneNumber**: The phone number of the user.
        /// - **PassWord**: The password for the account(Consists of at least 8 characters, with uppercase and lowercase letters).
        /// </remarks>
        /// <param name="registerRequest">The registration details for the user.</param>
        /// <returns>Returns a user response if the registration was successful.</returns>
        [HttpPost("account/register/user/doctor")]
        public async Task<IActionResult> RegisterUserDoctor([FromBody] RegisterRequest registerRequest)
        {
            if (registerRequest == null)
            {
                return BadRequest("Register request cannot be null.");
            }
            var userDto = await _repository.RegisterUser(registerRequest, true);
            return Ok(userDto != null ? userDto: null);
        }
        /// <summary>
        /// Registers a new user account for a staff.
        /// </summary>
        /// <remarks>
        /// This API allows the creation of a new user account with the provided registration details.
        ///
        /// **Request:**
        /// The request body must contain the user information:
        /// - **UserName**: The username for the account.
        /// - **PhoneNumber**: The phone number of the user.
        /// - **PassWord**: The password for the account(Consists of at least 8 characters, with uppercase and lowercase letters).
        /// </remarks>
        /// <param name="registerRequest">The registration details for the user.</param>
        /// <returns>Returns a user response if the registration was successful.</returns>
        [HttpPost("account/register/user/staff")]
        public async Task<IActionResult> RegisterUserStaff([FromBody] RegisterRequest registerRequest)
        {
            if (registerRequest == null)
            {
                return BadRequest("Register request cannot be null.");
            }
            var userDto = await _repository.RegisterUser(registerRequest, false);
            return Ok(userDto != null ? userDto : null);
        }

        /// <summary>
        /// Logs in a user and returns JWT and refresh tokens.
        /// </summary>
        /// <remarks>
        /// This API handles user login by verifying credentials and issuing tokens.
        ///
        /// **Request:**
        /// The request body must contain the login information:
        /// - **Email**: The user's email address.
        /// - **Password**: The user's password.
        /// </remarks>
        /// <param name="loginRequest">The login details for the user.</param>
        /// <returns>Returns JWT and refresh tokens if the login is successful and it saved in cookies</returns>
        [HttpPost("account/login")]
        public async Task<IActionResult> Login([FromBody] LoginRequest loginRequest)
        {
            _logger.LogInformation("Received login request for username: {UserName}", loginRequest?.Username);

            if (loginRequest == null)
            {
                _logger.LogWarning("Login request is null.");
                return BadRequest("Login request cannot be null.");
            }

            try
            {
                var (jwtToken, refreshToken) = await _repository.LoginUser(loginRequest);
                _logger.LogInformation("Login successful for username: {Username}. Returning tokens: JWT={JwtToken}, RefreshToken={RefreshToken}", loginRequest.Username, jwtToken, refreshToken);
                return Ok(new { JwtToken = jwtToken, RefreshToken = refreshToken });
            }
            catch (LoginFailedException ex)
            {
                return BadRequest(ex.Message);
            }
        }

        /// <summary>
        /// Refreshes a JWT token using a refresh token and returns a new JWT token and refresh token.
        /// </summary>
        /// <remarks>
        /// This API allows a client to refresh an expired JWT token by providing a valid refresh token.
        /// A new JWT token and a new refresh token will be issued if the provided refresh token is valid.
        ///
        /// **Request:**
        /// The request body must contain the refresh token:
        /// - **refreshTokenRequest**: The refresh token previously issued to the client.
        ///
        /// **Example Request:**
        /// ```http
        /// POST /api/account/refresh-token
        /// Content-Type: application/json
        /// {
        ///     "refreshTokenRequest": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
        /// }
        /// </remarks>
        /// <param name="refreshTokenRequest">The refresh token provided by the client to obtain a new JWT token.</param>
        /// <returns>
        /// Returns a new JWT token and a new refresh token if the refresh token is valid.
        /// Returns a 400 Bad Request if the refresh token is null or empty.
        /// </returns>
        [HttpPost("account/refresh-token")]
        public async Task<IActionResult> RefreshToken([FromBody] string refreshTokenRequest)
        {
            if (refreshTokenRequest == null || string.IsNullOrEmpty(refreshTokenRequest))
            {
                return BadRequest("Refresh token cannot be null or empty.");
            }

            var (jwtToken, newRefreshToken) = await _repository.CreateAccesstokenFromRefreshToken(refreshTokenRequest);
            return Ok(new { JwtToken = jwtToken, RefreshToken = newRefreshToken });
        }


    }
}
     

