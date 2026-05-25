import type { Meta, StoryObj } from "@storybook/react-vite";
import SchemaView from "@builder/SchemaView";
import { dataSchemaForTemplate } from "@builder/lib/dataSchema";
import { listExamples } from "@builder/lib/examples";
import type { JsonSchema, Template } from "@builder/types";
import schema from "./data/schema.json";
import examples from "./data/examples.json";

const template = (listExamples(examples)[0]?.template ?? {
  version: 1,
  config: {},
  rows: [],
}) as unknown as Template;
const dataSchema = dataSchemaForTemplate(schema as unknown as JsonSchema, template);

const meta: Meta<typeof SchemaView> = {
  title: "Builder/Schema View",
  component: SchemaView,
  decorators: [
    (Story) => (
      <div style={{ padding: 16 }}>
        <Story />
      </div>
    ),
  ],
};

export default meta;

type Story = StoryObj<typeof SchemaView>;

export const Invoice: Story = {
  args: {
    template,
    dataSchema,
    onExportTemplate: () => {},
  },
};
