
namespace IDentityServices.Exceptions
{
    public class LoginFailedException(string username) : Exception($"Invalid username: {username} or password.");

}
