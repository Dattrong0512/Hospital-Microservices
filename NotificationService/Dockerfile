# Stage 1: Build the application
FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

# Copy all project files and folders into the /src directory.
# This assumes your Dockerfile is at the root of your project folder
# (the same directory that contains NotificationService.csproj and your Services/Models/Messages folders).
COPY . .

# Restore NuGet packages. This should be done after all project files are copied.
# The .csproj file will now be found directly in /src.
RUN dotnet restore "NotificationService.csproj"

# Build the application. All source files are now available.
RUN dotnet build "NotificationService.csproj" -c Release -o /app/build

# Stage 2: Publish the application
FROM build AS publish
# Change WORKDIR back to /src for publishing, as the .csproj is there
WORKDIR /src
RUN dotnet publish "NotificationService.csproj" -c Release -o /app/publish /p:UseAppHost=false

# Stage 3: Create the final runtime image
FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
# Copy the published output from the 'publish' stage
COPY --from=publish /app/publish .

# Define the entry point for the application
ENTRYPOINT ["dotnet", "NotificationService.dll"]