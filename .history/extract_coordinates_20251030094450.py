import json

# List of barangay names to extract
names_to_extract = [
    "Bato",
    "Burabod",
    "Busali",
    "Canila",
    "Hugpa",
    "Julita",
    "Pinangumhan",
    "San Isidro",
    "San Roque",
    "Sanggalang",
    "Villa Enage"
]

# Load the GeoJSON file
with open('output_boundaries.geojson', 'r') as f:
    data = json.load(f)

# Dictionary to hold results
results = {}

# Iterate through features
for feature in data['features']:
    name = feature['properties']['name']
    if name in names_to_extract:
        results[name] = feature['geometry']['coordinates']

# Print the results
for name, coords in results.items():
    print(f"Name: {name}")
    print(f"Coordinates: {coords}")
    print("-" * 50)
