import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import CanvasZoomControls from "./CanvasZoomControls";

describe("CanvasZoomControls", () => {
  it("renders plus, minus, scale label, and default reset", () => {
    const html = renderToStaticMarkup(
      <CanvasZoomControls
        onDecrease={() => undefined}
        onIncrease={() => undefined}
        onReset={() => undefined}
      />,
    );

    expect(html).toContain("Scale");
    expect(html).toContain("Default");
    expect(html).not.toContain("132%");
    expect(html).toContain('aria-label="Decrease page scale"');
    expect(html).toContain('aria-label="Increase page scale"');
  });
});
