using System.ComponentModel.DataAnnotations.Schema;
using Microsoft.EntityFrameworkCore;

namespace ReportService.Models
{
    [Table("PrescriptionDetails")]
    [PrimaryKey(nameof(PrescriptionId), nameof(MedicineId))]
    public class PrescriptionDetail
    {
        [Column("prescription_id")]
        public int PrescriptionId { get; set; }

        [Column("medicine_id")]
        public int MedicineId { get; set; }

        [Column("amount")]
        public int Amount { get; set; }
    }
}
