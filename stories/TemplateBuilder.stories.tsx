import type { Meta, StoryObj } from "@storybook/react-vite";
import TemplateBuilder from "@builder/TemplateBuilder";
import { listExamples } from "@builder/lib/examples";
import type { DataMap, JsonSchema, Template } from "@builder/types";
import schema from "./data/schema.json";
import examples from "./data/examples.json";
import { renderPdfStub, renderStub } from "./support/renderStub";

const emptyTemplate: Template = { version: 1, config: {}, rows: [] };
const firstExample = listExamples(examples)[0];

const meta: Meta<typeof TemplateBuilder> = {
  title: "Builder/Template Builder",
  component: TemplateBuilder,
  tags: ["autodocs"],
  parameters: {
    layout: "fullscreen",
    docs: {
      description: {
        component:
          "The full template builder. Drag blocks from the palette onto the canvas, edit their config, and inspect the generated schema and data. The render tab is a static stub here — live rendering needs the PHP backend.",
      },
    },
  },
};

export default meta;

type Story = StoryObj<typeof TemplateBuilder>;

export const Empty: Story = {
  args: {
    schema: schema as unknown as JsonSchema,
    examples,
    initialTemplate: emptyTemplate,
    initialData: {},
    renderTemplate: renderStub,
    renderPdf: renderPdfStub,
  },
};

export const Invoice: Story = {
  args: {
    schema: schema as unknown as JsonSchema,
    examples,
    initialTemplate: (firstExample?.template ?? emptyTemplate) as unknown as Template,
    initialData: (firstExample?.data ?? {}) as DataMap,
    renderTemplate: renderStub,
    renderPdf: renderPdfStub,
  },
};
