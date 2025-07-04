using NotificationService.Models;
using NotificationService.Services;
using Microsoft.Extensions.Hosting;
using RabbitMQ.Client;


var builder = WebApplication.CreateBuilder(args);

// Đọc và bind cấu hình MongoDB
builder.Services.Configure<MongoDBSettings>(builder.Configuration.GetSection("MongoDB"));

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
// Learn more about configuring Swagger/OpenAPI at https://aka.ms/aspnetcore/swashbuckle
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

builder.Services.Configure<RabbitMQSettings>(builder.Configuration.GetSection("RabbitMQ"));
builder.Services.AddSingleton<IConnectionFactory>(sp =>
{
    var rabbitMQSettings = sp.GetRequiredService<Microsoft.Extensions.Options.IOptions<RabbitMQSettings>>().Value;
    var factory = new ConnectionFactory
    {
        Uri = new Uri(rabbitMQSettings.AmqpConnectionString)
    };
    return factory;
});

// Đăng ký Background Service cho RabbitMQ Consumer
builder.Services.AddHostedService<RabbitMQConsumerService>();
var app = builder.Build();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

app.UseHttpsRedirection();

app.UseAuthorization();

app.MapControllers();

app.Run();