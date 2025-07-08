using Microsoft.EntityFrameworkCore;
using ReportService.Models;

namespace ReportService.Data
{
    public class AppointmentDbContext : DbContext
    {
        public AppointmentDbContext(DbContextOptions<AppointmentDbContext> options)
            : base(options) { }
        public DbSet<Appointment> Appointments { get; set; } = default!;
    }

    public class PrescriptionDbContext : DbContext
    {
        public PrescriptionDbContext(DbContextOptions<PrescriptionDbContext> options)
            : base(options) { }
        public DbSet<Prescription> Prescriptions { get; set; } = default!;
        public DbSet<PrescriptionDetail> PrescriptionDetails { get; set; } = default!;
    }

    public class MedicineDbContext : DbContext
    {
        public MedicineDbContext(DbContextOptions<MedicineDbContext> options)
            : base(options) { }
        public DbSet<Medicine> Medicines { get; set; } = default!;
    }
}