FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY ["ReportService/ReportService.csproj", "ReportService/"]
RUN dotnet restore "ReportService/ReportService.csproj"

COPY . .
WORKDIR /src/AppointmentService

RUN dotnet publish "ReportService.csproj" -c Release -o /app/publish --no-restore \
    /p:UseAppHost=false \
    /p:PublishTrimmed=true

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app

COPY --from=build /app/publish .

EXPOSE 8080
ENTRYPOINT ["dotnet", "ReportService.dll"]