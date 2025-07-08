using Microsoft.AspNetCore.Builder;
using Microsoft.EntityFrameworkCore;
using ReportService.Data;
using ReportService.Models;
using ReportService.Services;
using Pomelo.EntityFrameworkCore.MySql.Infrastructure;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using System.IO;
using System;

var builder = WebApplication.CreateBuilder(args);


var appointmentConn = builder.Configuration.GetSection("ConnectionStrings")["AppointmentDb"];
var prescriptionConn = builder.Configuration.GetSection("ConnectionStrings")["PrescriptionDb"];
builder.Services.AddDbContext<AppointmentDbContext>(options =>
    options.UseMySql(appointmentConn, ServerVersion.AutoDetect(appointmentConn)));
builder.Services.AddDbContext<PrescriptionDbContext>(options =>
    options.UseMySql(prescriptionConn, ServerVersion.AutoDetect(prescriptionConn)));

var medicineConn = builder.Configuration.GetSection("ConnectionStrings")["MedicineDb"];
builder.Services.AddDbContext<MedicineDbContext>(options =>
    options.UseMySql(medicineConn, ServerVersion.AutoDetect(medicineConn)));

builder.Services.AddScoped<IReportService, ReportService.Services.ReportService>();

builder.Services.AddControllers();
builder.Services.AddApiVersioning(options =>
{
    options.AssumeDefaultVersionWhenUnspecified = true;
    options.DefaultApiVersion = new Microsoft.AspNetCore.Mvc.ApiVersion(1, 0);
    options.ReportApiVersions = true;
});
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen(options =>
{
    var xmlFilename = $"{System.Reflection.Assembly.GetExecutingAssembly().GetName().Name}.xml";
    options.IncludeXmlComments(System.IO.Path.Combine(AppContext.BaseDirectory, xmlFilename));
});

var app = builder.Build();

if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

app.UseHttpsRedirection();

app.UseAuthorization();

app.MapControllers();

app.Run();