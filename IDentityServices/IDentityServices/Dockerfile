# Stage 1: Build
FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /source

# Sao chép file .csproj để tận dụng layer caching
COPY IDentityServices/*.csproj ./IDentityServices/

# Xóa cache NuGet và phục hồi các gói NuGet
RUN dotnet nuget locals all --clear && dotnet restore IDentityServices/IDentityServices.csproj -v d

# Sao chép toàn bộ mã nguồn
COPY IDentityServices/. ./IDentityServices/

# Build và publish dự án API với log chi tiết
WORKDIR /source/IDentityServices
RUN dotnet publish -c Release -o /app -v d

# Stage 2: Runtime
FROM mcr.microsoft.com/dotnet/aspnet:8.0
WORKDIR /app
COPY --from=build /app ./

# Khởi chạy ứng dụng
ENTRYPOINT ["dotnet", "IDentityServices.dll"]