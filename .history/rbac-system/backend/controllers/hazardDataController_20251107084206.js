const { body, validationResult } = require('express-validator');

// Mock data for hazard data operations (replace with actual model when needed)
let hazardData = [
  { id: 1, type: 'Flood' },
  { id: 2, type: 'Earthquake' }
];

// @desc    Get all hazard data
// @route   GET /api/hazard-data
// @access  Private (with feature access)
exports.getHazardData = async (req, res) => {
  try {
    res.status(200).json({
      success: true,
      data: hazardData
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Create hazard data
// @route   POST /api/hazard-data
// @access  Private (with permission)
exports.createHazardData = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        message: 'Validation failed',
        errors: errors.array()
      });
    }

    const { type } = req.body;
    const newHazardData = {
      id: hazardData.length + 1,
      type
    };

    hazardData.push(newHazardData);

    res.status(201).json({
      success: true,
      data: newHazardData
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};
