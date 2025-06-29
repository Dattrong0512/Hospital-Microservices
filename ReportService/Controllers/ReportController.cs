using Microsoft.AspNetCore.Mvc;
using ReportService.Models;
using ReportService.Services;

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
        /// Lấy báo cáo số lượng bệnh nhân theo từng tháng.
        /// </summary>
        /// <returns>Danh sách thống kê số lượng bệnh nhân theo tháng. 200 nếu thành công, 500 nếu lỗi.</returns>
        [HttpGet("monthly-patient-statistics")]
        [ProducesResponseType(StatusCodes.Status200OK, Type = typeof(List<MonthlyPatientStats>))]
        [ProducesResponseType(StatusCodes.Status500InternalServerError)]
        public async Task<IActionResult> GetMonthlyPatientStatistics()
        {
            _logger.LogInformation("Received request for monthly patient statistics.");
            try
            {
                var stats = await _reportService.GetMonthlyPatientStatisticsAsync();
                return Ok(stats);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting monthly patient statistics.");
                return StatusCode(StatusCodes.Status500InternalServerError, "An error occurred while retrieving statistics.");
            }
        }

        /// <summary>
        /// Lấy báo cáo số lượng đơn thuốc đã cấp theo từng tháng.
        /// </summary>
        /// <returns>Danh sách thống kê số lượng đơn thuốc theo tháng. 200 nếu thành công, 500 nếu lỗi.</returns>
        [HttpGet("monthly-prescription-statistics")]
        [ProducesResponseType(StatusCodes.Status200OK, Type = typeof(List<MonthlyPrescriptionStats>))]
        [ProducesResponseType(StatusCodes.Status500InternalServerError)]
        public async Task<IActionResult> GetMonthlyPrescriptionStatistics()
        {
            _logger.LogInformation("Received request for monthly prescription statistics.");
            try
            {
                var stats = await _reportService.GetMonthlyPrescriptionStatisticsAsync();
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