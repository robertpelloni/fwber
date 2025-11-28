#!/usr/bin/env python3
"""Generate placeholder images for FWBer tier system demo."""
from PIL import Image, ImageDraw, ImageFont
import os

# Output directory
output_dir = r"C:\Users\hyper\workspace\fwber\fwber-backend\storage\app\public\photos\test"
os.makedirs(output_dir, exist_ok=True)

def create_placeholder(filename, label, bg_color, text_color):
    """Create a placeholder image with text."""
    # Create 800x800 image
    img = Image.new('RGB', (800, 800), color=bg_color)
    draw = ImageDraw.Draw(img)
    
    # Try to use a nice font, fall back to default if not available
    try:
        font = ImageFont.truetype("arial.ttf", 80)
        small_font = ImageFont.truetype("arial.ttf", 40)
    except:
        font = ImageFont.load_default()
        small_font = ImageFont.load_default()
    
    # Draw label text
    text = label
    bbox = draw.textbbox((0, 0), text, font=font)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]
    x = (800 - text_width) // 2
    y = (800 - text_height) // 2 - 50
    draw.text((x, y), text, fill=text_color, font=font)
    
    # Draw emoji below
    emoji_y = y + text_height + 40
    emoji = "🎨" if "AI" in label else "📸"
    emoji_bbox = draw.textbbox((0, 0), emoji, font=font)
    emoji_width = emoji_bbox[2] - emoji_bbox[0]
    emoji_x = (800 - emoji_width) // 2
    draw.text((emoji_x, emoji_y), emoji, font=font)
    
    # Save image
    filepath = os.path.join(output_dir, filename)
    img.save(filepath, 'JPEG', quality=85)
    print(f"Created: {filename}")

# Generate AI photos (2 photos)
create_placeholder('ai-photo-6-1.jpg', 'AI Photo 1', '#E3F2FD', '#1565C0')
create_placeholder('ai-photo-6-2.jpg', 'AI Photo 2', '#E8EAF6', '#3F51B5')

# Generate real photos (5 photos)
create_placeholder('real-photo-6-3.jpg', 'Real Photo 1', '#E8F5E9', '#2E7D32')
create_placeholder('real-photo-6-4.jpg', 'Real Photo 2', '#F1F8E9', '#558B2F')
create_placeholder('real-photo-6-5.jpg', 'Real Photo 3', '#FFF3E0', '#EF6C00')
create_placeholder('real-photo-6-6.jpg', 'Real Photo 4', '#FBE9E7', '#D84315')
create_placeholder('real-photo-6-7.jpg', 'Real Photo 5', '#FCE4EC', '#C2185B')

print(f"\n✅ Generated 7 placeholder images in {output_dir}")
