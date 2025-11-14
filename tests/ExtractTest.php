<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../extract.php';

class ExtractTest extends TestCase
{
    /**
     * Test organizePDFData with valid student data
     */
    public function testOrganizePDFDataWithStudentInfo()
    {
        $debugLogs = [];
        $extractedData = [
            'student_name' => 'John Doe',
            'dob' => '2010-05-15',
            'grade' => '7',
            'student_id' => 'STU12345',
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertEquals('John Doe', $result['student']['name']);
        $this->assertEquals('2010-05-15', $result['student']['dob']);
        $this->assertEquals('7', $result['student']['grade']);
        $this->assertEquals('STU12345', $result['student']['id']);
    }

    /**
     * Test organizePDFData with IEP dates
     */
    public function testOrganizePDFDataWithIEPDates()
    {
        $debugLogs = [];
        $extractedData = [
            'iep_meeting_date' => '2024-11-01',
            'effective_date' => '2024-11-15',
            'next_review_date' => '2025-11-01',
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertEquals('2024-11-01', $result['iep_dates']['meeting_date']);
        $this->assertEquals('2024-11-15', $result['iep_dates']['effective_date']);
        $this->assertEquals('2025-11-01', $result['iep_dates']['review_date']);
    }

    /**
     * Test organizePDFData with goals array
     */
    public function testOrganizePDFDataWithGoals()
    {
        $debugLogs = [];
        $extractedData = [
            'goals' => [
                'Improve reading comprehension to 80% accuracy',
                'Complete math assignments independently',
                'Participate in class discussions 3 times per week',
            ],
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertCount(3, $result['goals']);
        $this->assertContains('Improve reading comprehension to 80% accuracy', $result['goals']);
        $this->assertContains('Complete math assignments independently', $result['goals']);
    }

    /**
     * Test organizePDFData with accommodations array
     */
    public function testOrganizePDFDataWithAccommodations()
    {
        $debugLogs = [];
        $extractedData = [
            'accommodations' => [
                'Extended time on tests',
                'Preferential seating',
                'Use of calculator',
            ],
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertCount(3, $result['accommodations']);
        $this->assertContains('Extended time on tests', $result['accommodations']);
        $this->assertContains('Preferential seating', $result['accommodations']);
    }

    /**
     * Test organizePDFData with performance levels
     */
    public function testOrganizePDFDataWithPerformanceLevels()
    {
        $debugLogs = [];
        $extractedData = [
            'performance_levels' => [
                'Reading: Below grade level, currently at 4th grade level',
                'Math: At grade level with accommodations',
            ],
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertCount(2, $result['performance']);
        $this->assertContains('Reading: Below grade level, currently at 4th grade level', $result['performance']);
    }

    /**
     * Test organizePDFData with services array
     */
    public function testOrganizePDFDataWithServices()
    {
        $debugLogs = [];
        $extractedData = [
            'services' => [
                'Speech therapy - 30 minutes, 2x per week',
                'Occupational therapy - 45 minutes, 1x per week',
            ],
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertCount(2, $result['services']);
        $this->assertContains('Speech therapy - 30 minutes, 2x per week', $result['services']);
    }

    /**
     * Test organizePDFData with empty data
     */
    public function testOrganizePDFDataWithEmptyData()
    {
        $debugLogs = [];
        $extractedData = [];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('student', $result);
        $this->assertArrayHasKey('goals', $result);
        $this->assertArrayHasKey('accommodations', $result);
        $this->assertEquals('', $result['student']['name']);
        $this->assertEmpty($result['goals']);
    }

    /**
     * Test organizePDFData filters out empty values
     */
    public function testOrganizePDFDataFiltersEmptyValues()
    {
        $debugLogs = [];
        $extractedData = [
            'goals' => [
                'Valid goal',
                '',
                '   ',
                'Another valid goal',
            ],
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertCount(2, $result['goals']);
        $this->assertContains('Valid goal', $result['goals']);
        $this->assertContains('Another valid goal', $result['goals']);
    }

    /**
     * Test organizePDFData with disability information
     */
    public function testOrganizePDFDataWithDisabilityInfo()
    {
        $debugLogs = [];
        $extractedData = [
            'disability_category' => 'Specific Learning Disability',
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        $this->assertEquals('Specific Learning Disability', $result['disability']['category']);
    }

    /**
     * Test organizePDFData with complete IEP data
     */
    public function testOrganizePDFDataWithCompleteData()
    {
        $debugLogs = [];
        $extractedData = [
            'student_name' => 'Jane Smith',
            'dob' => '2012-03-20',
            'grade' => '5',
            'disability_category' => 'Autism Spectrum Disorder',
            'iep_meeting_date' => '2024-10-15',
            'goals' => [
                'Improve social communication skills',
                'Increase focus and attention span',
            ],
            'accommodations' => [
                'Visual schedule',
                'Sensory breaks',
            ],
            'services' => [
                'Behavioral support - 60 minutes, 3x per week',
            ],
        ];

        $result = organizePDFData($extractedData, $debugLogs);

        // Verify all sections are populated
        $this->assertEquals('Jane Smith', $result['student']['name']);
        $this->assertEquals('2012-03-20', $result['student']['dob']);
        $this->assertEquals('Autism Spectrum Disorder', $result['disability']['category']);
        $this->assertCount(2, $result['goals']);
        $this->assertCount(2, $result['accommodations']);
        $this->assertCount(1, $result['services']);
    }
}
