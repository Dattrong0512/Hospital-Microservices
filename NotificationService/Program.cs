using NotificationService.Models;
using NotificationService.Services;
using Microsoft.Extensions.Hosting;
using RabbitMQ.Client;
using Microsoft.OpenApi.Models;


var builder = WebApplication.CreateBuilder(args);

// Đọc và bind cấu hình MongoDB
builder.Services.Configure<MongoDBSettings>(builder.Configuration.GetSection("MongoDBSettings"));

// Đọc và bind cấu hình Mail
builder.Services.Configure<MailSettings>(builder.Configuration.GetSection("MailSettings"));

// Đăng ký NotificationService vào Dependency Injection Container
builder.Services.AddSingleton<INotificationService, NotificationService.Services.NotificationService>(); // Đảm bảo đúng namespace

// Add services to the container.
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
    var logger = sp.GetRequiredService<ILogger<Program>>(); // Get logger for better debugging

    if (!string.IsNullOrEmpty(rabbitMQSettings?.AmqpConnectionString))
    {
        var factory = new ConnectionFactory
        {
            Uri = new Uri(rabbitMQSettings.AmqpConnectionString)
        };

        // *******************************************************************
        // THIS IS THE CRUCIAL PART FOR 'PLAIN' AUTH MECHANISM ERROR
        // CloudAMQP might not allow PLAIN over AMQPS.
        // Let's explicitly set the allowed authentication mechanisms.
        // AMQPLAIN is often a good choice, or leave it for the library to decide.
        // If the 'PLAIN' error persists, you might need to adjust this further.
        // For CloudAMQP, often just setting the URI is enough,
        // but if it's explicitly failing with PLAIN, this helps.
        factory.AuthMechanisms = new IAuthMechanismFactory[] { new AmqPlainMechanismFactory() };
        // *******************************************************************
        
        logger.LogInformation($"Using RabbitMQ connection string: {rabbitMQSettings.AmqpConnectionString}");
        return factory;
    }
    else // Fallback to individual properties if no AMQP connection string (for local dev)
    {
        logger.LogInformation("Using individual RabbitMQ settings (Host, Port, User, Pass).");
        var factory = new ConnectionFactory
        {
            HostName = rabbitMQSettings?.HostName,
            Port = rabbitMQSettings?.Port ?? AmqpTcpEndpoint.DefaultAmqpPort,
            UserName = rabbitMQSettings?.UserName,
            Password = rabbitMQSettings?.Password
        };
        logger.LogInformation($"Using RabbitMQ individual settings: Host={rabbitMQSettings?.HostName}, Port={rabbitMQSettings?.Port}");
        return factory;
    }
});

// Đăng ký Background Service cho RabbitMQ Consumer
builder.Services.AddHostedService<RabbitMQConsumerService>();
var app = builder.Build();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI(options =>
    {
        options.SwaggerEndpoint("/swagger/v1/swagger.json", "Notification Service API v1");
    });
}

app.UseHttpsRedirection();

app.UseAuthorization();

app.MapControllers();

app.Run();