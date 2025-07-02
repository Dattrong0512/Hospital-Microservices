FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY ["NotificationService/NotificationService.csproj", "NotificationService/"]
RUN dotnet restore "NotificationService/NotificationService.csproj"

COPY . .
WORKDIR /src/NotificationService

RUN dotnet publish "NotificationService.csproj" -c Release -o /app/publish --no-restore \
    /p:UseAppHost=false \
    /p:PublishSingleFile=true \
    /p:PublishTrimmed=true

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app

COPY --from=build /app/publish .
ENTRYPOINT ["dotnet", "NotificationService.dll"]