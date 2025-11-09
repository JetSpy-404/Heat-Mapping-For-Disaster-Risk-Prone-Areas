const { body, validationResult } = require('express-validator');

// Mock data for municipality operations (replace with actual model when needed)
let municipalities = [
  { id: 1, name: 'Municipality 1' }
];

// @desc    Get all municipalities
// @route   GET /api/municipality
// @access  Private (with feature access)
exports.getMunicipalities = async (req, res) => {
  try {
    res.status(200).json({
      success: true,
      data: municipalities
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};

// @desc    Create municipality
// @route   POST /api/municipality
// @access  Private (with permission)
exports.createMunicipality = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        message: 'Validation failed',
        errors: errors.array()
      });
    }

    const { name } = req.body;
    const newMunicipality = {
      id: municipalities.length + 1,
      name
    };

    municipalities.push(newMunicipality);

    res.status(201).json({
      success: true,
      data: newMunicipality
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error.message
    });
  }
};
