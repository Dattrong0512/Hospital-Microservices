FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY NotificationService.csproj ./
COPY Models ./Models
COPY Services ./Services
COPY Controller ./Controller
COPY Properties ./Properties
COPY appsettings.json ./
COPY appsettings.Development.json ./
COPY Program.cs ./
COPY NotificationService.http ./

RUN dotnet restore "NotificationService.csproj"

RUN dotnet build "NotificationService.csproj" -c Release -o /app/build

FROM build AS publish
WORKDIR /src
RUN dotnet publish "NotificationService.csproj" -c Release -o /app/publish /p:UseAppHost=false

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
COPY --from=publish /app/publish .

ENV ASPNETCORE_ENVIRONMENT=Production
ENV ASPNETCORE_URLS=http://+:8088

ENTRYPOINT ["dotnet", "NotificationService.dll"]