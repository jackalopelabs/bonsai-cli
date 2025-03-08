/**
 * PixelMatrix - A class that creates an animated pixel effect on hover
 * for pricing boxes or other container elements.
 */
class PixelMatrix {
    constructor(container) {
        this.container = container;
        this.pixels = [];
        this.animationFrame = null;
        this.isAnimating = false;
        
        // Set colors based on plan type
        this.colors = this.getColorsForPlan();
        
        this.canvas = document.createElement('canvas');
        this.canvas.style.position = 'absolute';
        this.canvas.style.top = '0';
        this.canvas.style.left = '0';
        this.canvas.style.width = '100%';
        this.canvas.style.height = '100%';
        this.canvas.style.pointerEvents = 'none';
        this.canvas.style.opacity = '0.5';
        
        const context = this.canvas.getContext('2d');
        if (!context) throw new Error('Could not get canvas context');
        this.ctx = context;
        
        this.container.appendChild(this.canvas);
        this.init();
        
        this.container.addEventListener('mouseenter', () => this.startAnimation('appear'));
        this.container.addEventListener('mouseleave', () => this.startAnimation('disappear'));
        window.addEventListener('resize', () => this.init());
    }

    init() {
        const rect = this.container.getBoundingClientRect();
        this.canvas.width = rect.width;
        this.canvas.height = rect.height;
        this.generatePixels();
    }

    getColorsForPlan() {
        const planType = this.container.querySelector('h3')?.textContent?.trim();
        
        switch (planType) {
            case 'Basic':
                return [
                    { start: '#f9fafb', end: '#6b7280' }, // gray-50 to gray-500
                    { start: '#f2f3fa', end: '#4b5563' }, // gray-100 to gray-600
                    { start: '#ffffff', end: '#374151' }, // white to gray-700
                ];
            case 'Pro':
                return [
                    { start: '#eef2ff', end: '#4f46e5' }, // indigo-50 to indigo-600
                    { start: '#c7d2fe', end: '#3730a3' }, // indigo-200 to indigo-800
                    { start: '#818cf8', end: '#312e81' }, // indigo-400 to indigo-900
                ];
            case 'Sensei':
                return [
                    { start: '#E6FFFA', end: '#4f46e5' }, // teal-50 to indigo-600
                    { start: '#00FFB9', end: '#3730a3' }, // teal-500 to indigo-800
                    { start: '#00DB9D', end: '#1e1b4b' }, // teal-600 to indigo-950
                ];
            default:
                return [
                    { start: '#f9fafb', end: '#4b5563' }, // gray-50 to gray-600
                ];
        }
    }

    generatePixels() {
        this.pixels = [];
        const gap = 8;
        const gradients = this.getColorsForPlan();
        
        // Calculate center points
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        
        for (let x = 0; x < this.canvas.width; x += gap) {
            for (let y = 0; y < this.canvas.height; y += gap) {
                // Calculate distance from center
                const dx = x - centerX;
                const dy = y - centerY;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                const gradient = gradients[Math.floor(Math.random() * gradients.length)];
                
                this.pixels.push({
                    x,
                    y,
                    size: 0,
                    maxSize: this.getRandomValue(1.5, 2),
                    minSize: 0.5,
                    colorStart: gradient.start,
                    colorEnd: gradient.end,
                    colorPos: Math.random(),
                    speed: this.getRandomValue(0.1, 0.9) * 0.035,
                    counter: 0,
                    counterStep: Math.random() * 4 + (this.canvas.width + this.canvas.height) * 0.01,
                    isIdle: false,
                    isReverse: false,
                    isShimmer: false,
                    distance,
                });
            }
        }
    }

    getRandomValue(min, max) {
        return Math.random() * (max - min) + min;
    }

    startAnimation(type) {
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
        }
        this.animate(type);
    }

    animate(type) {
        this.animationFrame = requestAnimationFrame(() => this.animate(type));
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        const time = performance.now() / 1000;
        let allIdle = true;
        
        this.pixels.forEach(pixel => {
            if (type === 'appear') {
                pixel.isIdle = false;
                
                // Create ripple wave effect
                const rippleSpeed = 2;
                const rippleDelay = pixel.distance / 100;
                const wave = Math.sin(time * rippleSpeed - rippleDelay) * 0.5 + 0.5;
                
                if (pixel.counter <= pixel.counterStep * 50) {
                    pixel.counter += pixel.counterStep;
                    return;
                }

                if (pixel.size >= pixel.maxSize * 0.7) {
                    pixel.isShimmer = true;
                }

                if (pixel.isShimmer) {
                    this.shimmerPixel(pixel);
                    // Combine shimmer with ripple effect
                    pixel.size *= (0.8 + wave * 0.2);
                } else {
                    pixel.size += pixel.speed * 1.5 * (1 + wave);
                }
            } else {
                if (pixel.size <= 0) {
                    pixel.isIdle = true;
                } else {
                    pixel.size -= 0.05;
                }
            }

            if (!pixel.isIdle) allIdle = false;

            const centerOffset = pixel.maxSize * 0.5 - pixel.size * 0.5;
            
            // Calculate color based on gradient position and wave
            const color = this.interpolateColor(
                pixel.colorStart, 
                pixel.colorEnd, 
                (Math.sin(pixel.colorPos * Math.PI * 2 + time) + 1) / 2
            );
            
            this.ctx.fillStyle = color;
            this.ctx.fillRect(
                pixel.x + centerOffset,
                pixel.y + centerOffset,
                pixel.size,
                pixel.size
            );
            
            pixel.colorPos = (pixel.colorPos + 0.001) % 1;
        });
        
        if (allIdle && type === 'disappear') {
            cancelAnimationFrame(this.animationFrame);
        }
    }

    shimmerPixel(pixel) {
        if (pixel.size >= pixel.maxSize) {
            pixel.isReverse = true;
        } else if (pixel.size <= pixel.minSize) {
            pixel.isReverse = false;
        }

        if (pixel.isReverse) {
            pixel.size -= pixel.speed;
        } else {
            pixel.size += pixel.speed;
        }
    }

    interpolateColor(startColor, endColor, position) {
        // Convert hex to RGB
        const start = this.hexToRgb(startColor);
        const end = this.hexToRgb(endColor);
        
        // Interpolate between colors
        const r = Math.round(start.r + (end.r - start.r) * position);
        const g = Math.round(start.g + (end.g - start.g) * position);
        const b = Math.round(start.b + (end.b - start.b) * position);
        
        return `rgb(${r}, ${g}, ${b})`;
    }

    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16),
        } : { r: 0, g: 0, b: 0 };
    }

    destroy() {
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
        }
        this.container.removeChild(this.canvas);
        window.removeEventListener('resize', () => this.init());
    }
}

export default PixelMatrix; 