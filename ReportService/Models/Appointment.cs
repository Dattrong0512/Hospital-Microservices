using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;
using System.Linq;
using Microsoft.AspNetCore.Builder;

namespace ReportService.Models
{
    [Table("Appointment")]
    public class Appointment
    {
        [Key]
        [Column("appointment_id")]
        public int AppointmentId { get; set; }

        [Column("patient_id")]
        public int PatientId { get; set; }

        [Column("date")]
        public DateTime Date { get; set; }

    }
} 