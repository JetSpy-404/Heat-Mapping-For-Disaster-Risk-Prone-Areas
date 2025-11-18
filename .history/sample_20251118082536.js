// Hazard severity colors
const hazardSusceptibilityColors = {
    very_high: '#bc00c0', // Purple
    high: '#ff0000',     // Red
    moderate:  '#eee200', // Yellow
    low: '#0bd900'       // Green
};

// Notification styles
const notificationStyles = `
.notification {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    padding: 15px 20px !important;
    border-radius: 5px !important;
    color: white !important;
    font-weight: bold !important;
    z-index: 10000 !important;
    opacity: 0 !important;
    transform: translateX(100%) !important;
    transition: all 0.3s ease !important;
    max-width: 300px !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}
.notification.show {
    opacity: 1 !important;
    transform: translateX(0) !important;
}
.notification.success {
    background-color: #10b981 !important;
}
.notification.error {
    background-color: #ef4444 !important;
}
.notification.danger {
    background-color: #ef4444 !important;
}
.notification.warning {
    background-color: #f59e0b !important;
}
.notification.info {
    background-color: #3b82f6 !important;
}
.notification .close-btn {
    float: right !important;
    margin-left: 10px !important;
    cursor: pointer !important;
    font-weight: bold !important;
    color: white !important;
}
`;

// Inject styles into the document
const styleElement = document.createElement('style');
styleElement.textContent = notificationStyles;
document.head.appendChild(styleElement);

// Heatmap gradient
const heatmapGradient = {
    0.3: hazardSusceptibilityColors.low,    // Low
    0.6: hazardSusceptibilityColors.moderate,  // Moderate
    0.8: hazardSusceptibilityColors.high,    // High
    1.0: hazardSusceptibilityColors.very_high   // Very High
};

// Global variables
let currentHeatmapLayer = null;

// Update the legend when hazard type is selected
function updateLegend(hazardType) {
    const legendContent = document.getElementById('legendContent');

    if (!hazardType) {
        legendContent.innerHTML = '';
    } else {
        // Show severity levels for selected hazard type
        const hazardName = $('#hazardSelect option:selected').text();
        legendContent.innerHTML = `
            <div class="legend-title">${hazardName} Susceptibility</div>
            <div class="legend-item"><i style="background: ${hazardSusceptibilityColors.very_high};"></i> Very High</div>
            <div class="legend-item"><i style="background: ${hazardSusceptibilityColors.high};"></i> High</div>
            <div class="legend-item"><i style="background: ${hazardSusceptibilityColors.moderate};"></i> Moderate</div>
            <div class="legend-item"><i style="background: ${hazardSusceptibilityColors.low};"></i> Low</div>
        `;
    }
}

// Enhanced loadHeatmapData function with proper error handling
function loadHeatmapData(hazardType, municipality = null, barangay = null) {
    // Clear existing layers first
    clearHeatmapLayers();

    // If no hazard type selected, return early
    if (!hazardType) {
        updateLegend(null);
        return;
    }

    // Show loading state
    showLoadingState(true);

    try {
        // Only load barangay-specific data
        if (barangay && municipality) {
            // Load specific barangay data
            loadBarangaySpecificData(hazardType, municipality, barangay);
        } else {
            // If no barangay selected, show notification
            showLoadingState(false);
            showNotification('Please select a barangay to view hazard data.', 'info');
            updateLegend(null);
        }
    } catch (error) {
        console.error('Error in loadHeatmapData:', error);
        showLoadingState(false);
        showError('Failed to process heatmap data request.');
    }
}

// Load data for a specific barangay with enhanced error handling
function loadBarangaySpecificData(hazardType, municipality, barangay) {
    // Validate inputs
    if (!municipality || !barangay) {
        throw new Error('Municipality and barangay are required for barangay-specific data');
    }

    // Construct the path to barangay-specific GeoJSON file based on your structure
    const barangayDataPath = `assets/data/${municipality.toLowerCase()}/${barangay.toLowerCase()}_${hazardType}.geojson`;

    console.log(`Attempting to load barangay data from: ${barangayDataPath}`);

    // Add cache-busting parameter to ensure fresh data
    const timestamp = new Date().getTime();
    const urlWithCacheBust = `${barangayDataPath}?t=${timestamp}`;

    fetch(urlWithCacheBust, {
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Barangay data not found (HTTP ${response.status})`);
        }
        return response.json();
    })
    .then(data => {
        // Validate GeoJSON structure
        if (!data || !data.features || !Array.isArray(data.features)) {
            throw new Error('Invalid GeoJSON format: missing features array');
        }

        // Check if data has actual features (not empty)
        if (data.features.length === 0) {
            throw new Error('Barangay data file exists but contains no features');
        }

        console.log(`Successfully loaded barangay data for ${barangay}`);
        displayHeatmapData(data, municipality, barangay, hazardType);
        showLoadingState(false);
    })
    .catch(error => {
        console.warn(`Barangay data load failed for ${barangay}:`, error.message);
        
        // Clear any existing heatmap
        clearHeatmapLayers();
        
        // Show specific notification based on error type
        if (error.message.includes('not found') || error.message.includes('HTTP 404')) {
            showNotification(`No ${hazardType} data available for Barangay ${barangay}.`, 'warning');
        } else if (error.message.includes('no features')) {
            showNotification(`No ${hazardType} hazard data found for Barangay ${barangay}.`, 'warning');
        } else {
            showNotification(`Failed to load ${hazardType} data for Barangay ${barangay}.`, 'warning');
        }
        
        updateLegend(null);
        showLoadingState(false);
    });
}

// Display heatmap data on the map with enhanced error handling
function displayHeatmapData(data, municipality, barangay, hazardType) {
    try {
        // Clear existing layers first
        clearHeatmapLayers();

        if (!data || !data.features || data.features.length === 0) {
            showNotification('No heatmap data available for the selected area.', 'warning');
            return;
        }

        // Validate and prepare heatmap data with intensity based on LH values
        const heatData = data.features.map(feature => {
            try {
                if (!feature.geometry || !feature.geometry.coordinates) {
                    console.warn('Invalid feature geometry:', feature);
                    return null;
                }

                let lat, lng;

                // Handle different geometry types
                if (feature.geometry.type === 'Point') {
                    [lng, lat] = feature.geometry.coordinates;
                } else {
                    // For polygons and other geometries, use center of mass
                    let center;
                    try {
                        center = window.turf?.centerOfMass?.(feature);
                    } catch (e) {
                        console.warn('Error calculating center of mass:', e, feature);
                        center = null;
                    }
                    if (!center) {
                        center = { geometry: { coordinates: [124.4642, 11.5833] } };
                    }
                    [lng, lat] = center.geometry.coordinates;
                }

                // Use LH value for intensity (normalize to 0-1 range)
                const lhValue = feature.properties?.LH || 1;
                const intensity = Math.min(lhValue / 3, 1); // Normalize since max LH is 3

                // Validate coordinates
                if (typeof lat !== 'number' || typeof lng !== 'number' ||
                    lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    console.warn('Invalid coordinates:', { lat, lng, feature });
                    return null;
                }

                return [lat, lng, intensity];
            } catch (error) {
                console.warn('Error processing feature:', error, feature);
                return null;
            }
        }).filter(point => point !== null); // Remove invalid points

        if (heatData.length === 0) {
            showNotification('No valid heatmap points found in the data.', 'warning');
            return;
        }

        // Create heatmap layer with appropriate radius settings
        currentHeatmapLayer = L.heatLayer(heatData, {
            radius: getRadiusForZoom(map.getZoom()),
            blur: 15, // Increased blur for smoother heatmap
            maxZoom: 17,
            minOpacity: 0.8,
            maxOpacity: 0.9,
            gradient: heatmapGradient
        });

        currentHeatmapLayer.addTo(map);

        // Update map view to fit the heatmap data
        const bounds = getHeatmapBounds(heatData);
        if (bounds) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }

        // Highlight selected boundary using existing boundariesLayer
        if (municipality || barangay) {
            highlightSelectedBoundary(municipality, barangay);
        }

        // Update legend
        updateLegend(hazardType);

        // Show data summary
        showDataSummary(heatData.length, municipality, barangay);

        // Show success notification
        showNotification('Heatmap data loaded successfully!', 'success');

    } catch (error) {
        console.error('Error displaying heatmap data:', error);
        showError('Failed to display heatmap data. The data format may be invalid.');
    }
}

// Helper function to calculate bounds from heatmap data
function getHeatmapBounds(heatData) {
    if (!heatData || heatData.length === 0) return null;
    
    let minLat = Infinity, maxLat = -Infinity;
    let minLng = Infinity, maxLng = -Infinity;
    
    heatData.forEach(point => {
        const [lat, lng] = point;
        minLat = Math.min(minLat, lat);
        maxLat = Math.max(maxLat, lat);
        minLng = Math.min(minLng, lng);
        maxLng = Math.max(maxLng, lng);
    });
    
    return [[minLat, minLng], [maxLat, maxLng]];
}

// Clear existing heatmap layers
function clearHeatmapLayers() {
    if (currentHeatmapLayer) {
        if (map.hasLayer(currentHeatmapLayer)) {
            map.removeLayer(currentHeatmapLayer);
        }
        currentHeatmapLayer = null;
    }

    // Additional cleanup for any orphaned heat layers
    map.eachLayer((layer) => {
        if (layer instanceof L.HeatLayer) {
            map.removeLayer(layer);
        }
    });
}

// Highlight selected boundary using the existing boundariesLayer
function highlightSelectedBoundary(municipality, barangay) {
    // Clear existing boundaries first
    boundariesLayer.clearLayers();

    try {
        if (barangay && municipality && municipalities[municipality]) {
            const barangayData = municipalities[municipality].barangays.find(
                b => b.name.toLowerCase() === barangay.toLowerCase()
            );

            if (barangayData && barangayData.boundary) {
                const boundary = L.polygon(barangayData.boundary, {
                    color: '#ff0000',
                    weight: 3,
                    fillColor: '#ff0000',
                    fillOpacity: 0.0
                }).addTo(boundariesLayer);

                // Fit map to the barangay boundary
                map.fitBounds(boundary.getBounds());
                console.log(`Highlighted barangay boundary: ${barangay}`);
            }
        } else if (municipality && municipalities[municipality]) {
            const boundary = L.polygon(municipalities[municipality].boundary, {
                color: '#ff0000',
                weight: 3,
                fillColor: '#ff0000',
                fillOpacity: 0.1
            }).addTo(boundariesLayer);

            // Fit map to the municipality boundary
            map.fitBounds(boundary.getBounds());
            console.log(`Highlighted municipality boundary: ${municipality}`);
        }
    } catch (error) {
        console.error('Error highlighting boundary:', error);
        showNotification('Failed to highlight boundary. Boundary data may be invalid.', 'warning');
    }
}

// Dynamic radius based on zoom level - IMPROVED with larger values
function getRadiusForZoom(zoom) {
    const radiusMap = {
        8: 50,
        9: 45,
        10: 40,
        11: 35,
        12: 30,
        13: 25,
        14: 20,
        15: 15,
        16: 12,
        17: 10,
        18: 8,
        19: 6,
        20: 4
    };
    return radiusMap[zoom] || 25;
}

// Enhanced UI feedback functions
function showLoadingState(show) {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        if (show) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            submitBtn.disabled = true;
        } else {
            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Submit';
            submitBtn.disabled = false;
        }
    }
}

function showDataSummary(featureCount, municipality, barangay) {
    let locationText = barangay ? `Barangay ${barangay}, ${municipality}` : `Municipality of ${municipality}`;

    const message = `Displaying ${featureCount} heatmap points for ${locationText}`;
    console.log(message);
}

function showNotification(message, type = 'info') {
    // Create notification using custom styles
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        ${message}
        <span class="close-btn">&times;</span>
    `;

    document.body.appendChild(notification);

    // Trigger show animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    // Close button functionality
    const closeBtn = notification.querySelector('.close-btn');
    closeBtn.addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.classList.contains('show')) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

function showError(message) {
    console.error('Heatmap Error:', message);
    showNotification(message, 'danger');
}

// Data validation and debugging functions
function validateMunicipalitiesData() {
    console.log('=== Validating Municipalities Data ===');
    Object.keys(municipalities).forEach(mun => {
        console.log(`Municipality: ${mun}`);
        if (municipalities[mun].barangays) {
            console.log(`  Barangays: ${municipalities[mun].barangays.length}`);
        }
    });
}

function checkDataFiles() {
    console.log('=== Checking Data Files ===');
    // Since we're only using barangay data, we don't need to check province files
    console.log('Barangay-specific data files will be checked when selected');
}

// Initialize required scripts with error handling
$(document).ready(function() {
    console.log('Initializing heatmap system...');

    // Validate data structure
    validateMunicipalitiesData();

    // Check data files
    checkDataFiles();

    // Load required scripts with error handling
    const loadScript = (src, globalVar) => {
        return new Promise((resolve, reject) => {
            if (window[globalVar]) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
            document.head.appendChild(script);
        });
    };

    Promise.all([
        loadScript('assets/js/heatmaps.js', 'L.HeatLayer').catch(error => {
            console.warn('Heatmaps.js not loaded:', error.message);
        }),
        loadScript('assets/js/turf.min.js', 'turf').catch(error => {
            console.warn('Turf.js not loaded:', error.message);
        })
    ]).finally(() => {
        // Initialize event listeners regardless of script loading
        initializeEventListeners();
        console.log('Heatmap system initialized');
    });
});

// Initialize the map centered on Biliran
const map = L.map('biliranMap').setView([11.5833, 124.4642], 10);

// Base maps with proper attributions
const lightMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
});

const darkMap = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
});

const streetMap = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
});

const satelliteMap = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
});

const terrainMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://opentopomap.org">OpenTopoMap</a> contributors'
});

// Global variables
let heatmapLayer = null;

// Add default base layer - this will automatically show the proper attribution
terrainMap.addTo(map);

// Layer group for boundaries
const boundariesLayer = L.layerGroup().addTo(map);

// Add this to your script section
document.getElementById('mapTypeToggle').addEventListener('click', function() {
    const dropdown = document.querySelector('.map-type-dropdown .dropdown-content');
    if (dropdown.style.display === 'flex') {
        dropdown.style.display = 'none';
    } else {
        dropdown.style.display = 'flex';
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.map-type-dropdown');
    if (!dropdown.contains(event.target)) {
        document.querySelector('.map-type-dropdown .dropdown-content').style.display = 'none';
    }
});

// Map Control Functions
const MapControls = {
    // Custom pointer marker
    customPointer: null,

    // Create a custom pointer marker
    createCustomPointer(latlng, title) {
        // Remove existing pointer if any
        if (this.customPointer) {
            map.removeLayer(this.customPointer);
        }

        // Create a new custom pointer
        this.customPointer = L.marker(latlng, {
            icon: L.divIcon({
                className: 'custom-pointer',
                html: '',
                iconSize: [20, 20]
            })
        }).addTo(map);

        // Add popup if title is provided
        if (title) {
            this.customPointer.bindPopup(`<b>${title}</b>`).openPopup();
        }
    },

    // Draw municipality boundary
    drawMunicipalityBoundary(municipality) {
        boundariesLayer.clearLayers();
        if (municipalities[municipality]?.boundary) {
            L.polygon(municipalities[municipality].boundary, {
                color: '#ff7800',
                weight: 2.0,
                fillColor: '#ff7800',
                fillOpacity: 0.2
            }).addTo(boundariesLayer);
        }
    },

    // Update hazard markers
    updateHazardMarkers(hazardType) {
        currentMarkers.clearLayers();

        if (hazardType && hazardData[hazardType]) {
            hazardData[hazardType].forEach(hazard => {
                L.marker(hazard.coords, {
                    icon: hazardIcons[hazardType]
                })
                .bindPopup(`
                    <b>${hazard.name}</b><br>
                    Type: ${$('#hazardSelect option:selected').text()}<br>
                    Severity: ${hazard.severity}<br>
                    Date: ${hazard.date}
                `)
                .addTo(currentMarkers);
            });
        }
    },

    // Reset map to default state
    resetMap() {
        $('#municipalitySelect').val('').trigger('change');
        $('#barangaySelect').val('');
        $('#hazardSelect').val('');
        updateLegend(null);
        map.setView([11.5833, 124.4642], 10);

        // Clear all layers
        clearHeatmapLayers();
        if (this.customPointer) {
            map.removeLayer(this.customPointer);
            this.customPointer = null;
        }
        boundariesLayer.clearLayers();
    },

    // Change base map layer
    changeBaseLayer(layer) {
        map.eachLayer(l => {
            if (l instanceof L.TileLayer) {
                map.removeLayer(l);
            }
        });
        layer.addTo(map);
    }
};

// Event Handlers
const EventHandlers = {
    // Municipality select change
    onMunicipalityChange() {
        const selectedMun = $(this).val();
        const barangaySelect = $('#barangaySelect');

        barangaySelect.empty().append('<option value="">Select Barangay</option>');

        if (selectedMun && municipalities[selectedMun]) {
            barangaySelect.prop('disabled', false);
            municipalities[selectedMun].barangays.forEach(barangay => {
                barangaySelect.append(`<option value="${barangay.name.toLowerCase()}">${barangay.name}</option>`);
            });

            // Center map and add pointer
            const munCoords = municipalities[selectedMun].coords;
            map.setView(munCoords, 12);
            MapControls.createCustomPointer(munCoords, $(this).find('option:selected').text());

            // Draw municipality boundary
            MapControls.drawMunicipalityBoundary(selectedMun);

            // Clear any existing heatmap when only municipality is selected
            clearHeatmapLayers();
            updateLegend(null);

        } else {
            barangaySelect.prop('disabled', true);
            if (MapControls.customPointer) {
                map.removeLayer(MapControls.customPointer);
                MapControls.customPointer = null;
            }
            boundariesLayer.clearLayers();
            clearHeatmapLayers();
            updateLegend(null);
        }
    },

    // Barangay select change
    onBarangayChange() {
        const selectedMun = $('#municipalitySelect').val();
        const selectedBrgy = $(this).val();

        boundariesLayer.clearLayers();

        if (selectedMun && selectedBrgy) {
            const barangay = municipalities[selectedMun].barangays.find(b => b.name.toLowerCase() === selectedBrgy);
            if (barangay) {
                map.setView(barangay.coords, 14);
                MapControls.createCustomPointer(barangay.coords, $(this).find('option:selected').text());

                // Draw barangay boundary
                if (barangay.boundary) {
                    L.polygon(barangay.boundary, {
                        color: '#ff7800',
                        weight: 2.0,
                        fillColor: '#ff7800',
                        fillOpacity: 0.0
                    }).addTo(boundariesLayer);
                }
            }

            // Load heatmap for barangay if hazard is selected
            const selectedHazard = $('#hazardSelect').val();
            if (selectedHazard) {
                loadHeatmapData(selectedHazard, selectedMun, selectedBrgy);
            } else {
                showNotification('Please select a hazard type to view data.', 'info');
            }
        } else {
            // If barangay is deselected, clear heatmap
            clearHeatmapLayers();
            updateLegend(null);
            
            // Show municipality boundary if municipality is selected
            if (selectedMun) {
                const munCoords = municipalities[selectedMun].coords;
                map.setView(munCoords, 12);
                MapControls.createCustomPointer(munCoords, $('#municipalitySelect').find('option:selected').text());
                MapControls.drawMunicipalityBoundary(selectedMun);
            }
        }
    },

    // Hazard select change
    onHazardChange() {
        const selectedHazard = $(this).val();
        const selectedMun = $('#municipalitySelect').val();
        const selectedBrgy = $('#barangaySelect').val();

        updateLegend(selectedHazard);
        
        // Only load heatmap if barangay is selected
        if (selectedMun && selectedBrgy) {
            loadHeatmapData(selectedHazard, selectedMun, selectedBrgy);
        } else if (selectedHazard) {
            showNotification('Please select a barangay to view hazard data.', 'info');
            clearHeatmapLayers();
        } else {
            clearHeatmapLayers();
        }
    },

    // Toggle boundaries
    onToggleBoundaries() {
        if (map.hasLayer(boundariesLayer)) {
            map.removeLayer(boundariesLayer);
            $(this).text('Show Boundaries');
        } else {
            map.addLayer(boundariesLayer);
            $(this).text('Hide Boundaries');
            // Redraw boundaries if municipality is selected
            const selectedMun = $('#municipalitySelect').val();
            if (selectedMun) {
                MapControls.drawMunicipalityBoundary(selectedMun);
            }
        }
    },

    // Submit form
    onSubmit() {
        const municipality = $('#municipalitySelect').val();
        const barangay = $('#barangaySelect').val();
        const hazard = $('#hazardSelect').val();

        if (!municipality || !barangay || !hazard) {
            showNotification('Please select a municipality, barangay, and hazard type.', 'warning');
            return;
        }

        // In a real app, this would submit to a server
        console.log('Submitted:', { municipality, barangay, hazard });
        showNotification('Selection submitted (check console for details)', 'success');
        
        // Load the heatmap data
        loadHeatmapData(hazard, municipality, barangay);
    }
};

// Initialize Event Listeners
function initializeEventListeners() {
    // Dropdown changes
    $('#municipalitySelect').change(EventHandlers.onMunicipalityChange);
    $('#barangaySelect').change(EventHandlers.onBarangayChange);
    $('#hazardSelect').change(EventHandlers.onHazardChange);

    // Map type buttons
    $('#darkBtn').click(() => MapControls.changeBaseLayer(darkMap));
    $('#lightBtn').click(() => MapControls.changeBaseLayer(lightMap));
    $('#streetBtn').click(() => MapControls.changeBaseLayer(streetMap));
    $('#satelliteBtn').click(() => MapControls.changeBaseLayer(satelliteMap));
    $('#terrainBtn').click(() => MapControls.changeBaseLayer(terrainMap));

    // Action buttons
    $('#toggleBoundariesBtn').click(EventHandlers.onToggleBoundaries);
    $('#resetBtn').click(MapControls.resetMap);
    $('#submitBtn').click(EventHandlers.onSubmit);

    // Window resize
    $(window).resize(() => map.invalidateSize());
}

// Initialize the application
$(document).ready(function() {
    initializeEventListeners();
    loadUserData();
});

// Load user data and update the dropdown
    async function loadUserData() {
        try {
            const res = await fetch('api/users/current.php');
            const data = await res.json();
            if (data.success) {
                Alpine.store('user', data.data);
                updateUserDropdown(data.data);
                updateSidebarRole(data.data.role);
            }
        } catch (err) {
            console.error('Failed to load user data:', err);
        }
    }

    // Update the user dropdown with fetched data
    function updateUserDropdown(user) {
        const dropdown = document.querySelector('.dropdown.flex-shrink-0');
        if (dropdown) {
            const triggerImg = dropdown.querySelector('a img');
            const dropdownImg = dropdown.querySelector('ul li img');
            const nameSpan = dropdown.querySelector('h4');
            const emailA = dropdown.querySelector('ul li a');

            if (triggerImg) triggerImg.src = user.profile_picture || 'assets/images/logo.jpg';
            if (dropdownImg) dropdownImg.src = user.profile_picture || 'assets/images/logo.jpg';
            if (nameSpan) {
                nameSpan.innerHTML = `${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}<span class="rounded bg-success-light px-1 text-xs text-success ltr:ml-2 rtl:ml-2">Pro</span>`;
            }
            if (emailA) emailA.textContent = user.email;
        }
    }

    // Update the sidebar role display
   function updateSidebarRole(role) {
       const sidebarRoleElement = document.getElementById('sidebar-role');
       if (sidebarRoleElement) {
           // Capitalize the first letter of the role
            const displayRole = role.charAt(0).toUpperCase() + role.slice(1);
            sidebarRoleElement.textContent = displayRole;
        }

        // Also update the header role display
        const headerRoleElement = document.getElementById('header-role');
        if (headerRoleElement) {
            const displayRole = role.charAt(0).toUpperCase() + role.slice(1);
            headerRoleElement.textContent = displayRole;
            }
        }

        // Initialize the application
        async function init() {
            await fetchUsers();
            initEventListeners();
        }

    // Start the application when DOM is loaded
    document.addEventListener('DOMContentLoaded', init);

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function (s) {
            return ({
                '&': '&amp;',
                '<': '<',
                '>': '>',
                '"': '"',
                "'": '&#39;'
            })[s];
        });
    }