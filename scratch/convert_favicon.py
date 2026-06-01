import os
from PIL import Image

def convert_png_to_favicon(src_png_path, dest_ico_path):
    print(f"Loading Unnati logo from: {src_png_path}")
    if not os.path.exists(src_png_path):
        print(f"Error: Source PNG file not found at {src_png_path}")
        return
        
    img = Image.open(src_png_path)
    
    # Save as multi-size ICO file for perfect display on standard browsers and high-DPI screens
    print(f"Saving multi-size ICO to: {dest_ico_path}...")
    img.save(
        dest_ico_path,
        format="ICO",
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64), (128, 128)]
    )
    print("Favicon converted and saved successfully!")

if __name__ == "__main__":
    src_png = r"c:\Users\Gaurav\StatelyWorld\Projects\Flutter\sw-unnati\assets\images\logo.png"
    dest_ico = r"c:\Users\Gaurav\StatelyWorld\Projects\Laravel\swapi\public\favicon.ico"
    convert_png_to_favicon(src_png, dest_ico)
