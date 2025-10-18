// Initialize the map centered on Biliran
const map = L.map('chloroplethMap').setView([11.5833, 124.4642], 10);

// Base maps
const satelliteMap = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
});

const darkMap = L.tileLayer('https://tiles.wmflabs.org/bright/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
});

// Add default base layer
satelliteMap.addTo(map);

// Declare variables
let geojson;
let floodLayer;
let landslideLayer;
let stormSurgeLayer;
let heatmapLayer;
let currentlyHighlighted = null;

// Add Heatmap plugin
const heatScript = document.createElement('script');
heatScript.src = 'https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js';
document.head.appendChild(heatScript);

// Updated color scale with smooth gradient interpolation
function getColor(d) {
    const colorStops = [
        { value: 10, color: [11, 217, 0] },   // #0bd900 (Low)
        { value: 40, color: [238, 226, 0] },  // #eee200 (Moderate)
        { value: 75, color: [255, 0, 0] },    // #ff0000 (High)
        { value: 100, color: [188, 0, 192] }   // #bc00c0 (Very High)
    ];
    
    let lowerStop = colorStops[0];
    let upperStop = colorStops[colorStops.length - 1];
    
    for (let i = 0; i < colorStops.length - 1; i++) {
        if (d >= colorStops[i].value && d <= colorStops[i + 1].value) {
            lowerStop = colorStops[i];
            upperStop = colorStops[i + 1];
            break;
        }
    }
    
    const range = upperStop.value - lowerStop.value;
    const factor = range > 0 ? (d - lowerStop.value) / range : 0;
    
    const r = Math.round(lowerStop.color[0] + factor * (upperStop.color[0] - lowerStop.color[0]));
    const g = Math.round(lowerStop.color[1] + factor * (upperStop.color[1] - lowerStop.color[1]));
    const b = Math.round(lowerStop.color[2] + factor * (upperStop.color[2] - lowerStop.color[2]));
    
    return `rgb(${r}, ${g}, ${b})`;
}

// Style functions for hazard layers
function floodStyle(feature) {
    return {
        fillColor: '#e70000',
        weight: 1,
        opacity: 1,
        color: '#000000',
        dashArray: '3',
        fillOpacity: 0.4
    };
}

function landslideStyle(feature) {
    return {
        fillColor: '#c000ac',
        weight: 1,
        opacity: 1,
        color: '#000000',
        dashArray: '3',
        fillOpacity: 0.4
    };
}

function stormSurgeStyle(feature) {
    return {
        fillColor: '#f9e600',
        weight: 1,
        opacity: 1,
        color: '#000000',
        dashArray: '3',
        fillOpacity: 0.4
    };
}

// Main style function
function style(feature) {
    const hazardType = $('#hazardSelect').val();
    const value = hazardType ? feature.properties[hazardType] : 0;
    return {
        fillColor: getColor(value),
        weight: 2.0,
        opacity: 1,
        color: '#000000',
        dashArray: '3',
        fillOpacity: 0
    };
}

// Function to create heatmap from GeoJSON
function createHeatmap(data) {
    const heatData = data.features.map(feature => {
        const [lng, lat] = feature.geometry.coordinates;
        // Use intensity value or default to 1
        const intensity = feature.properties.intensity || feature.properties.value || 1;
        return [lat, lng, intensity];
    });

    return L.heatLayer(heatData, {
        radius: 20,
        blur: 15,
        maxZoom: 100,
        minOpacity: 0.5,
        gradient: {
            0.3: '#0bd900',  // Low
            0.6: '#eee200',  // Moderate
            0.8: '#ff0000',  // High
            1.0: '#bc00c0'   // Very High
        }
    });
}

// Improved highlight function
function highlightFeature(e) {
    // Reset any previously highlighted feature
    if (currentlyHighlighted) {
        resetHighlight({ target: currentlyHighlighted });
    }
    
    const layer = e.target;
    currentlyHighlighted = layer;
    
    // Save the original style
    if (!layer._originalStyle) {
        layer._originalStyle = {
            weight: layer.options.weight,
            color: layer.options.color,
            dashArray: layer.options.dashArray,
            fillOpacity: layer.options.fillOpacity
        };
    }
    
    // Apply highlight style
    layer.setStyle({
        weight: 2.0,
        color: '#fff',
        dashArray: '',
        fillOpacity: 0
    });
    
    layer.bringToFront();
    
    // Update info panel
    const props = layer.feature.properties;
    const hazardType = $('#hazardSelect').val();
    const hazardValue = hazardType ? props[hazardType] : 'N/A';
    
    // Get population risk data for the selected hazard type
    let riskData, hazardTitle;
    
    switch(hazardType) {
        case 'landslide':
            riskData = props.landslide_risk_population || {
                notProne: 0,
                low: 0,
                moderate: 0,
                high: 0,
                veryHigh: 0,
                totalProne: 0
            };
            hazardTitle = 'Rain-induced Landslide';
            break;
            
        case 'flood':
            riskData = props.flood_risk_population || {
                notProne: 0,
                low: 0,
                moderate: 0,
                high: 0,
                veryHigh: 0,
                totalProne: 0
            };
            hazardTitle = 'Flood';
            break;
            
        case 'storm surge':
            riskData = props.storm_surge_risk_population || {
                notProne: 0,
                low: 0,
                moderate: 0,
                high: 0,
                veryHigh: 0,
                totalProne: 0
            };
            hazardTitle = 'Storm Surge';
            break;
            
        default:
            riskData = null;
    }
    
    $('#info-title').text(props.name);
    
    let riskInfo = '';
    if (hazardType && riskData) {
        riskInfo = `
            <div style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 8px;">
                <b>Population Risk to ${hazardTitle}:</b><br>
                <div style="display: flex; justify-content: space-between;">
                    <span>Not prone:</span>
                    <span>${riskData.notProne.toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Low Susceptibility:</span>
                    <span>${riskData.low.toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Moderate Susceptibility:</span>
                    <span>${riskData.moderate.toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>High Susceptibility:</span>
                    <span>${riskData.high.toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Very High Susceptibility:</span>
                    <span>${riskData.veryHigh.toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 5px; font-weight: bold;">
                    <span>Total Prone:</span>
                    <span>${riskData.totalProne.toLocaleString()}</span>
                </div>
            </div>
        `;
    } else if (!hazardType) {
        riskInfo = `
            <div style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 8px;">
                <i>Please select a hazard type to view population risk data</i>
            </div>
        `;
    }
    
    $('#info-content').html(`
        <b>Hazard Type:</b> ${hazardType ? $('#hazardSelect option:selected').text() : 'Not selected'}<br>
        <b>Risk Level:</b> ${hazardValue}%<br>
        <b>Total Population:</b> ${props.population.toLocaleString()}
        ${riskInfo}
        <div class="text-end mt-2">
            <button class="btn btn-sm btn-outline-light" onclick="$('#info').hide(); resetHighlight({ target: currentlyHighlighted }); currentlyHighlighted = null;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    `);
    $('#info').show();
}

// Improved reset function
function resetHighlight(e) {
    const layer = e.target;
    if (layer._originalStyle) {
        layer.setStyle(layer._originalStyle);
    } else {
        geojson.resetStyle(layer);
    }
}

// Click to zoom
function zoomToFeature(e) {
    map.fitBounds(e.target.getBounds());
}

// On each feature for hazard layers
function onEachHazardFeature(feature, layer) {
    const hazardType = $('#hazardSelect').val();
    layer.bindPopup(`<b>${hazardType.charAt(0).toUpperCase() + hazardType.slice(1)} Hazard Zone</b>`);
}

// On each feature for main layer
function onEachFeature(feature, layer) {
    layer.on({
        mouseover: highlightFeature,
        mouseout: function(e) {
            if (e.target !== currentlyHighlighted) {
                resetHighlight(e);
            }
        },
        click: zoomToFeature
    });
}

// Toggle hazard layers with heatmap support
function toggleHazardLayers() {
    const hazardType = $('#hazardSelect').val();
    
    // Remove all layers first
    if (floodLayer && map.hasLayer(floodLayer)) map.removeLayer(floodLayer);
    if (landslideLayer && map.hasLayer(landslideLayer)) map.removeLayer(landslideLayer);
    if (stormSurgeLayer && map.hasLayer(stormSurgeLayer)) map.removeLayer(stormSurgeLayer);
    if (heatmapLayer && map.hasLayer(heatmapLayer)) map.removeLayer(heatmapLayer);
    
    // Add the appropriate layer and load corresponding heatmap
    switch(hazardType) {
        case 'flood':
            if (floodLayer) floodLayer.addTo(map);
            loadHeatmapData('flood.geojson');
            break;
        case 'landslide':
            if (landslideLayer) landslideLayer.addTo(map);
            loadHeatmapData('landslide.geojson');
            break;
        case 'storm surge':
            if (stormSurgeLayer) stormSurgeLayer.addTo(map);
            loadHeatmapData('storm_surge.geojson');
            break;
    }
}

// Load main GeoJSON with heatmap support
fetch('output_boundaries.geojson')
    .then(response => response.json())
    .then(data => {
        geojson = L.geoJSON(data, {
            style: style,
            onEachFeature: onEachFeature
        }).addTo(map);
        map.fitBounds(geojson.getBounds());
    });

// Load heatmap data
function loadHeatmapData(url) {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            window.heatmapData = data;
            if (heatmapLayer && map.hasLayer(heatmapLayer)) {
                map.removeLayer(heatmapLayer);
            }
            heatmapLayer = createHeatmap(data);
            heatmapLayer.addTo(map);
        })
        .catch(error => {
            console.error('Error loading heatmap data:', error);
        });
}

// Update choropleth when hazard type changes
$('#hazardSelect').change(function() {
    geojson.setStyle(style);
    toggleHazardLayers();
    updateLegend();
});

// Update legend
function updateLegend() {
    const hazardType = $('#hazardSelect').val();
    if (hazardType) {
        $('#legend h6').html(`<i class="fas fa-map-marked-alt"></i> ${$('#hazardSelect option:selected').text()}_Susceptibility`);
        $('#legend').show();
    } else {
        $('#legend').hide();
    }
}

// Boundary button (toggle municipal boundaries)
$('#boundaryBtn').click(function() {
    if (map.hasLayer(geojson)) {
        map.removeLayer(geojson);
    } else {
        geojson.addTo(map);
    }
});

// Reset button with heatmap support
$('#resetBtn').click(function() {
    $('#hazardSelect').val('');
    $('#timeSelect').val('current');
    geojson.setStyle(style);
    map.setView([11.5833, 124.4642], 10);
    $('#legend').hide();
    $('#info').hide();
    
    if (currentlyHighlighted) {
        resetHighlight({ target: currentlyHighlighted });
        currentlyHighlighted = null;
    }
    
    // Remove all layers
    if (floodLayer && map.hasLayer(floodLayer)) map.removeLayer(floodLayer);
    if (landslideLayer && map.hasLayer(landslideLayer)) map.removeLayer(landslideLayer);
    if (stormSurgeLayer && map.hasLayer(stormSurgeLayer)) map.removeLayer(stormSurgeLayer);
    if (heatmapLayer && map.hasLayer(heatmapLayer)) map.removeLayer(heatmapLayer);
});

// Initialize
updateLegend();