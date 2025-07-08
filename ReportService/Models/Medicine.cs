using System;
using System.ComponentModel.DataAnnotations.Schema;

namespace ReportService.Models
{
    [Table("Medicine")]
    public class Medicine
    {
        [Column("medicine_id")]
        public int MedicineId { get; set; }
        [Column("name")]
        public string Name { get; set; } = string.Empty;
        [Column("MFG")]
        public DateTime MFG { get; set; }
        [Column("EXP")]
        public DateTime EXP { get; set; }
        [Column("amount")]
        public int Amount { get; set; }
        [Column("unit")]
        public string Unit { get; set; } = string.Empty;
        [Column("price")]
        public int Price { get; set; }
    }
} 