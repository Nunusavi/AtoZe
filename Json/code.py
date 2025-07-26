import json
import pandas as pd

# Load the JSON data
with open('normalized_products.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# Flatten features and images for Excel
for item in data:
    item['features'] = ', '.join(item.get('features', []))
    item['image'] = ', '.join(item.get('image', []))

# Create DataFrame and export to Excel
df = pd.DataFrame(data)
df.to_excel('products.xlsx', index=False)
print("Exported to products.xlsx")