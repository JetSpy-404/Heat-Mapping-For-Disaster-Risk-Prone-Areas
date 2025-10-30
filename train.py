import json

def filter_and_swap_geojson(input_file, output_file, adm2_filter="Biliran"):
    """
    Process GeoJSON file to:
    1. Filter features where ADM2_EN matches the filter value
    2. Swap longitude and latitude coordinates
    3. Save to new file
    """
    try:
        # Read input file
        with open(input_file, 'r') as f:
            data = json.load(f)
        
        # Validate GeoJSON structure
        if data.get('type') != 'FeatureCollection':
            raise ValueError("Input file must be a GeoJSON FeatureCollection")
        
        # Filter features where ADM2_EN matches our filter
        filtered_features = [
            feature for feature in data['features']
            if feature.get('properties', {}).get('ADM2_EN') == adm2_filter
        ]
        
        if not filtered_features:
            print(f"No features found with ADM2_EN = '{adm2_filter}'")
            return False
        
        # Swap coordinates in filtered features
        for feature in filtered_features:
            geometry = feature['geometry']
            if geometry['type'] in ['MultiPolygon', 'Polygon']:
                # Handle both MultiPolygon and Polygon the same way
                polygons = geometry['coordinates'] if geometry['type'] == 'MultiPolygon' else [geometry['coordinates']]
                for polygon in polygons:
                    for ring in polygon:
                        for i, coord in enumerate(ring):
                            if len(coord) >= 2:  # Ensure it has at least lon and lat
                                ring[i] = [coord[1], coord[0]]  # Swap coordinates
        
        # Create new FeatureCollection with filtered and swapped features
        output_data = {
            "type": "FeatureCollection",
            "features": filtered_features
        }
        
        # Write output file
        with open(output_file, 'w') as f:
            json.dump(output_data, f, indent=2)
        
        print(f"Success! {len(filtered_features)} features exported to {output_file}")
        return True
    
    except FileNotFoundError:
        print(f"Error: File '{input_file}' not found")
    except json.JSONDecodeError:
        print(f"Error: '{input_file}' is not valid JSON")
    except Exception as e:
        print(f"Error: {str(e)}")
    
    return False

if __name__ == "__main__":
    print("Biliran GeoJSON Processor")
    print("Filters features where ADM2_EN = 'Biliran' and swaps coordinates")
    
    input_file = input("Enter input GeoJSON file path: ").strip()
    output_file = input("Enter output file path (press Enter for default): ").strip()
    
    if not output_file:
        # Generate default output filename
        if input_file.endswith('.geojson'):
            output_file = input_file.replace('.geojson', '_biliran_swapped.geojson')
        else:
            output_file = input_file + '_biliran_swapped.geojson'
    
    # Process with our specific filter for Biliran
    filter_and_swap_geojson(input_file, output_file, adm2_filter="Biliran")