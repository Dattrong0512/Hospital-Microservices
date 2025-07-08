using Microsoft.AspNetCore.Mvc;
using ReportService.Models;
using ReportService.Services;
using System.Threading.Tasks;
using System.Collections.Generic;
using Microsoft.Extensions.Logging;
using Microsoft.AspNetCore.Http;
using System;

namespace ReportService.Controllers
{
    [ApiController]
    [ApiVersion("0")]
    [Route("api/v{version:apiVersion}/[controller]")]
    public class ReportController : ControllerBase
    {
        private readonly IReportService _reportService;
        private readonly ILogger<ReportController> _logger;

        public ReportController(IReportService reportService, ILogger<ReportController> logger)
        {
            _reportService = reportService;
            _logger = logger;
        }
        /// <summary>
        /// Thống kê số lượng bệnh nhân duy nhất theo từng tháng/năm.
        /// </summary>
        /// <param name="year">Năm cần thống kê (tùy chọn, nếu không truyền sẽ lấy tất cả năm).</param>
        /// <param name="month">Tháng cần thống kê (tùy chọn, nếu không truyền sẽ lấy tất cả tháng).</param>
        /// <returns>Danh sách thống kê số lượng bệnh nhân theo tháng/năm.</returns>
        [HttpGet("monthly-patient-statistics")]
        [ProducesResponseType(StatusCodes.Status200OK, Type = typeof(List<MonthlyPatientStats>))]
        [ProducesResponseType(StatusCodes.Status500InternalServerError)]
        public async Task<IActionResult> GetMonthlyPatientStatistics([FromQuery] int? year, [FromQuery] int? month)
        {
            _logger.LogInformation("Received request for monthly patient statistics.");
            try
            {
                var stats = await _reportService.GetMonthlyPatientStatisticsAsync(year, month);
                return Ok(stats);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting monthly patient statistics.");
                return StatusCode(StatusCodes.Status500InternalServerError, "An error occurred while retrieving statistics.");
            }
        }

        /// <summary>
        /// Thống kê số lượng thuốc đã cấp theo từng tên thuốc của từng tháng/năm.
        /// </summary>
        /// <param name="year">Năm cần thống kê (tùy chọn, nếu không truyền sẽ lấy tất cả năm).</param>
        /// <param name="month">Tháng cần thống kê (tùy chọn, nếu không truyền sẽ lấy tất cả tháng).</param>
        /// <returns>Danh sách thống kê số lượng thuốc theo tên thuốc và tháng/năm.</returns>
        [HttpGet("monthly-prescription-statistics")]
        [ProducesResponseType(StatusCodes.Status200OK, Type = typeof(List<MonthlyPrescriptionStats>))]
        [ProducesResponseType(StatusCodes.Status500InternalServerError)]
        public async Task<IActionResult> GetMonthlyPrescriptionStatistics([FromQuery] int? year, [FromQuery] int? month)
        {
            _logger.LogInformation("Received request for monthly prescription statistics.");
            try
            {
                var stats = await _reportService.GetMonthlyPrescriptionStatisticsAsync(year, month);
                return Ok(stats);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting monthly prescription statistics.");
                return StatusCode(StatusCodes.Status500InternalServerError, "An error occurred while retrieving statistics.");
            }
        }
    }
}