import Foundation
import Vision
import AppKit

let path = "/Applications/MAMP/htdocs/coovix/deliverables/assets/erbil-booking-qr.png"
guard let image = NSImage(contentsOfFile: path),
      let data = image.tiffRepresentation,
      let bitmap = NSBitmapImageRep(data: data),
      let cgImage = bitmap.cgImage else {
    fatalError("Unable to load QR image")
}

let request = VNDetectBarcodesRequest()
request.symbologies = [.qr]
let handler = VNImageRequestHandler(cgImage: cgImage, options: [:])
try handler.perform([request])
let payloads = (request.results ?? []).compactMap { $0.payloadStringValue }
guard payloads.contains("https://erbilsc.com/booking/ESC-18472") else {
    fatalError("QR payload was not detected correctly: \(payloads)")
}
print(payloads[0])
