import type { Meta, StoryObj } from "@storybook/react-vite";
import { DndContext } from "@dnd-kit/core";
import BlockPalette from "@builder/BlockPalette";
import { listExamples } from "@builder/lib/examples";
import type { JsonSchema } from "@builder/types";
import schema from "./data/schema.json";
import examples from "./data/examples.json";

const meta: Meta<typeof BlockPalette> = {
  title: "Builder/Block Palette",
  component: BlockPalette,
  decorators: [
    (Story) => (
      <DndContext>
        <div style={{ width: 260, padding: 16 }}>
          <Story />
        </div>
      </DndContext>
    ),
  ],
};

export default meta;

type Story = StoryObj<typeof BlockPalette>;

export const Default: Story = {
  args: {
    schema: schema as unknown as JsonSchema,
    examples: listExamples(examples),
    pageConfig: {},
    onLoadExample: () => {},
    onUpdateTemplateConfig: () => {},
  },
};
