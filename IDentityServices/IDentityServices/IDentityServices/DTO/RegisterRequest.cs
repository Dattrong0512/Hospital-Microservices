namespace IDentityServices.DTO
{
    public class RegisterRequest
    {
        public required string UserName { get; init; }
        public required string PassWord { get; init; }
        public required string PhoneNumber { get; init; }

    }
}
