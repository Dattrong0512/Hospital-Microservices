using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace ReportService.Models
{
    [Table("Prescription")]
    public class Prescription
    {
        [Key]
        [Column("prescription_id")]
        public int PrescriptionId { get; set; }

        [Column("appointment_id")]
        public int AppointmentId { get; set; }

    }
}