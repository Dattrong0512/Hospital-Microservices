using NotificationService.Models;
using NotificationService.Services;
using Microsoft.Extensions.Hosting;
using Microsoft.OpenApi.Models;
using RabbitMQ.Client;

var builder = WebApplication.CreateBuilder(args);

builder.Services.Configure<MongoDBSettings>(builder.Configuration.GetSection("MongoDBSettings"));

builder.Services.Configure<MailSettings>(builder.Configuration.GetSection("MailSettings"));

builder.Services.AddSingleton<INotificationService, NotificationService.Services.NotificationService>();

builder.Services.AddControllers();
builder.Services.AddApiVersioning(options =>
{
    options.AssumeDefaultVersionWhenUnspecified = true;
    options.DefaultApiVersion = new Microsoft.AspNetCore.Mvc.ApiVersion(0, 0);
    options.ReportApiVersions = true;
});
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen(options =>
{
    options.SwaggerDoc("v1", new OpenApiInfo
    {
        Title = "Notification Service API",
        Description = "API for sending and managing notifications in the Hospital Microservices system.",
    });

    var xmlFilename = $"{System.Reflection.Assembly.GetExecutingAssembly().GetName().Name}.xml";
    options.IncludeXmlComments(Path.Combine(AppContext.BaseDirectory, xmlFilename));
});

builder.Services.Configure<RabbitMQSettings>(builder.Configuration.GetSection("RabbitMQ"));

builder.Services.AddSingleton<IConnectionFactory>(sp =>
{
    var config = sp.GetRequiredService<IConfiguration>();
    var rabbitMQSettings = config.GetSection("RabbitMQ").Get<RabbitMQSettings>();
    var logger = sp.GetRequiredService<ILogger<Program>>();

    if (rabbitMQSettings == null)
    {
        logger.LogError("RabbitMQ settings are missing from configuration. Ensure 'RabbitMQ' section is defined.");
        throw new InvalidOperationException("RabbitMQ settings are not configured.");
    }

    if (!string.IsNullOrEmpty(rabbitMQSettings.AmqpConnectionString))
    {
        var factory = new ConnectionFactory
        {
            Uri = new Uri(rabbitMQSettings.AmqpConnectionString),
            AutomaticRecoveryEnabled = true
        };
        logger.LogInformation($"Using RabbitMQ connection string: {rabbitMQSettings.AmqpConnectionString}");
        return factory;
    }
    else
    {
        logger.LogWarning("AmqpConnectionString is missing. Falling back to individual RabbitMQ settings (Host, Port, User, Pass).");
        var factory = new ConnectionFactory
        {
            HostName = rabbitMQSettings.HostName ?? "localhost",
            Port = rabbitMQSettings.Port ?? 5672,
            UserName = rabbitMQSettings.UserName ?? "guest",
            Password = rabbitMQSettings.Password ?? "guest",
            AutomaticRecoveryEnabled = true
        };
        logger.LogInformation($"Using RabbitMQ individual settings: Host={factory.HostName}, Port={factory.Port}");
        return factory;
    }
});

builder.Services.AddHostedService<RabbitMQConsumerService>();
var app = builder.Build();

if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI(options =>
    {
        options.SwaggerEndpoint("/swagger/v1/swagger.json", "Notification Service API v1");
    });
}

app.UseAuthorization();

app.MapControllers();

app.Run();