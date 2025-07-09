# Build stage
FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY ["ReportService.csproj", "./"]
RUN dotnet restore "ReportService.csproj"

COPY . .
RUN dotnet publish "ReportService.csproj" -c Release -o /app/publish /p:UseAppHost=false

# Runtime stage
FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
COPY --from=build /app/publish .

EXPOSE 8089
ENV ASPNETCORE_URLS=http://+:8080

ENTRYPOINT ["dotnet", "ReportService.dll"]