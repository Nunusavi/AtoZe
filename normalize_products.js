const fs = require('fs');

const raw = fs.readFileSync('./product_cataloge.json', 'utf-8');
const data = JSON.parse(raw);

const categories = [
    { key: 'CCTV', label: 'CCTV' },
    { key: 'NVR_and_DVR', label: 'NVR & DVR' },
    { key: 'Gate_Openers', label: 'Gate Openers' },
    { key: 'Intercoms', label: 'Intercoms' },
    { key: 'Time_Attendance', label: 'Time Attendance' }
];

const models = [];

function pushModel({ category, brand, model, description, features, image }) {
    models.push({
        category,
        brand,
        model,
        description: description || "",
        features: Array.isArray(features) ? features : (typeof features === 'object' ? Object.entries(features).map(([k, v]) => `${k}: ${Array.isArray(v) ? v.join(', ') : v}`) : []),
        image: Array.isArray(image) ? image : (image ? [image] : [])
    });
}

categories.forEach(cat => {
    const catData = data[cat.key];
    if (!catData) return;
    Object.entries(catData).forEach(([brand, items]) => {
        if (Array.isArray(items)) {
            items.forEach(item => {
                pushModel({
                    category: cat.key,
                    brand,
                    model: item.model || brand,
                    description: item.description || "",
                    features: item.features || item.Features || [],
                    image: item.image || []
                });
            });
        } else if (typeof items === 'object' && items !== null) {
            // For objects with Models, Components, Variants, or just a single model
            if (items.model) {
                pushModel({
                    category: cat.key,
                    brand,
                    model: items.model,
                    description: items.description || "",
                    features: items.features || items.Features || [],
                    image: items.image || []
                });
            } else if (items.Models) {
                items.Models.forEach(modelName => {
                    pushModel({
                        category: cat.key,
                        brand,
                        model: modelName,
                        description: items.description || "",
                        features: items.features || items.Features || [],
                        image: items.image || []
                    });
                });
            } else if (items.Components) {
                items.Components.forEach(modelName => {
                    pushModel({
                        category: cat.key,
                        brand,
                        model: modelName,
                        description: items.description || "",
                        features: items.features || items.Features || [],
                        image: items.image || []
                    });
                });
            } else if (items.Variants) {
                items.Variants.forEach(modelName => {
                    pushModel({
                        category: cat.key,
                        brand,
                        model: modelName,
                        description: items.description || "",
                        features: items.features || items.Features || [],
                        image: items.image || []
                    });
                });
            } else {
                // fallback: treat as a single model
                pushModel({
                    category: cat.key,
                    brand,
                    model: brand,
                    description: items.description || "",
                    features: items.features || items.Features || [],
                    image: items.image || []
                });
            }
        }
    });
});

fs.writeFileSync('./normalized_products.json', JSON.stringify(models, null, 2));
console.log('Normalized product data written to normalized_products.json');