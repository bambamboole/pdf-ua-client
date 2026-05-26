import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import CanvasZoomControls from "./CanvasZoomControls";

describe("CanvasZoomControls", () => {
  it("renders plus, minus, current scale, and default reset", () => {
    const html = renderToStaticMarkup(
      <CanvasZoomControls
        scale={1.32}
        defaultScale={1.32}
        onDecrease={() => undefined}
        onIncrease={() => undefined}
        onReset={() => undefined}
      />,
    );

    expect(html).toContain("132%");
    expect(html).toContain("Default 132%");
    expect(html).toContain('aria-label="Decrease page scale"');
    expect(html).toContain('aria-label="Increase page scale"');
  });
});
